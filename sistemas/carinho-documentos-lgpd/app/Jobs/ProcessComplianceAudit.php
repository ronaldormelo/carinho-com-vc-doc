<?php

namespace App\Jobs;

use App\Integrations\Crm\CrmClient;
use App\Integrations\Integracoes\IntegracoesClient;
use App\Services\ComplianceReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para auditoria periodica de conformidade LGPD.
 *
 * Executado semanalmente para:
 * - Gerar relatorio de conformidade
 * - Identificar riscos e nao conformidades
 * - Alertar gestao sobre problemas
 * - Documentar estado de compliance para auditorias externas
 */
class ProcessComplianceAudit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600; // 10 minutos

    public function handle(
        ComplianceReportService $complianceService,
        CrmClient $crm,
        IntegracoesClient $integracoes
    ): void {
        Log::info('Starting weekly compliance audit');

        try {
            // Gera relatorio da ultima semana
            $report = $complianceService->generateComplianceReport(
                now()->subWeek(),
                now()
            );

            // Analisa resultados
            $auditResult = $this->analyzeReport($report);

            // Notifica conforme resultado
            $this->processAuditResult($auditResult, $report, $crm, $integracoes);

            // Registra auditoria
            $this->logAuditResult($auditResult, $report);

            Log::info('Weekly compliance audit completed', [
                'score' => $report['compliance_score']['total_score'],
                'classification' => $report['compliance_score']['classification'],
                'risk_count' => count($report['risk_indicators']),
            ]);
        } catch (\Throwable $e) {
            Log::error('Compliance audit failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Notifica falha na auditoria
            $this->notifyAuditFailure($e, $crm, $integracoes);
        }
    }

    private function analyzeReport(array $report): array
    {
        $score = $report['compliance_score']['total_score'];
        $risks = $report['risk_indicators'];

        // Determina severidade geral
        $severity = match (true) {
            $score < 40 || $this->hasCriticalRisks($risks) => 'critical',
            $score < 60 || $this->hasHighRisks($risks) => 'high',
            $score < 75 || $this->hasMediumRisks($risks) => 'medium',
            default => 'low',
        };

        // Determina se acao imediata e necessaria
        $requiresImmediateAction = $severity === 'critical' ||
            $report['lgpd_requests']['overdue_count'] > 0;

        // Gera pontos de atencao
        $attentionPoints = $this->generateAttentionPoints($report);

        return [
            'severity' => $severity,
            'requires_immediate_action' => $requiresImmediateAction,
            'score' => $score,
            'classification' => $report['compliance_score']['classification'],
            'attention_points' => $attentionPoints,
            'risk_count' => count($risks),
            'critical_risks' => $this->filterRisksBySeverity($risks, 'critical'),
            'high_risks' => $this->filterRisksBySeverity($risks, 'high'),
        ];
    }

    private function hasCriticalRisks(array $risks): bool
    {
        return collect($risks)->where('severity', 'critical')->isNotEmpty();
    }

    private function hasHighRisks(array $risks): bool
    {
        return collect($risks)->where('severity', 'high')->isNotEmpty();
    }

    private function hasMediumRisks(array $risks): bool
    {
        return collect($risks)->where('severity', 'medium')->isNotEmpty();
    }

    private function filterRisksBySeverity(array $risks, string $severity): array
    {
        return collect($risks)->where('severity', $severity)->values()->toArray();
    }

    private function generateAttentionPoints(array $report): array
    {
        $points = [];

        // Solicitacoes LGPD
        if ($report['lgpd_requests']['overdue_count'] > 0) {
            $points[] = [
                'area' => 'LGPD',
                'issue' => "Solicitacoes vencidas: {$report['lgpd_requests']['overdue_count']}",
                'action' => 'Processar imediatamente',
            ];
        }

        if ($report['lgpd_requests']['deadline_compliance_rate'] < 100) {
            $points[] = [
                'area' => 'LGPD',
                'issue' => "Taxa de cumprimento de prazo: {$report['lgpd_requests']['deadline_compliance_rate']}%",
                'action' => 'Melhorar processo de atendimento',
            ];
        }

        // Retencao
        foreach ($report['retention_status'] as $retention) {
            if (!$retention['compliant']) {
                $points[] = [
                    'area' => 'Retencao',
                    'issue' => "Documentos expirados ({$retention['doc_type']}): {$retention['expired_not_archived']}",
                    'action' => 'Executar politica de arquivamento',
                ];
            }
        }

        // Score geral
        if ($report['compliance_score']['total_score'] < 75) {
            $points[] = [
                'area' => 'Geral',
                'issue' => "Score de conformidade baixo: {$report['compliance_score']['total_score']}",
                'action' => 'Revisar processos de compliance',
            ];
        }

        return $points;
    }

    private function processAuditResult(
        array $auditResult,
        array $report,
        CrmClient $crm,
        IntegracoesClient $integracoes
    ): void {
        // Notifica CRM com resultado da auditoria
        try {
            $crm->createAuditReport([
                'type' => 'compliance_audit',
                'severity' => $auditResult['severity'],
                'score' => $auditResult['score'],
                'classification' => $auditResult['classification'],
                'attention_points' => $auditResult['attention_points'],
                'generated_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send audit report to CRM', ['error' => $e->getMessage()]);
        }

        // Publica evento de auditoria
        try {
            $integracoes->publishEvent('lgpd.compliance.audit', [
                'severity' => $auditResult['severity'],
                'score' => $auditResult['score'],
                'classification' => $auditResult['classification'],
                'requires_immediate_action' => $auditResult['requires_immediate_action'],
                'risk_count' => $auditResult['risk_count'],
                'audited_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to publish audit event', ['error' => $e->getMessage()]);
        }

        // Se acao imediata necessaria, cria alerta especial
        if ($auditResult['requires_immediate_action']) {
            try {
                $crm->createAlert([
                    'type' => 'compliance_critical',
                    'severity' => 'critical',
                    'message' => 'Auditoria de conformidade identificou problemas criticos. Acao imediata necessaria.',
                    'data' => [
                        'critical_risks' => $auditResult['critical_risks'],
                        'attention_points' => $auditResult['attention_points'],
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send critical alert', ['error' => $e->getMessage()]);
            }
        }
    }

    private function logAuditResult(array $auditResult, array $report): void
    {
        // Log estruturado para auditoria
        Log::channel('audit')->info('Weekly compliance audit completed', [
            'audit_date' => now()->toIso8601String(),
            'period' => [
                'start' => now()->subWeek()->toIso8601String(),
                'end' => now()->toIso8601String(),
            ],
            'result' => [
                'severity' => $auditResult['severity'],
                'score' => $auditResult['score'],
                'classification' => $auditResult['classification'],
                'risk_count' => $auditResult['risk_count'],
            ],
            'metrics' => [
                'lgpd_requests' => $report['lgpd_requests'],
                'consents' => $report['consents'],
            ],
            'attention_points' => $auditResult['attention_points'],
        ]);
    }

    private function notifyAuditFailure(\Throwable $e, CrmClient $crm, IntegracoesClient $integracoes): void
    {
        try {
            $crm->createAlert([
                'type' => 'audit_failure',
                'severity' => 'high',
                'message' => 'Falha na execucao da auditoria de conformidade',
                'data' => [
                    'error' => $e->getMessage(),
                    'occurred_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $ex) {
            Log::error('Failed to notify audit failure', ['error' => $ex->getMessage()]);
        }

        try {
            $integracoes->publishEvent('lgpd.compliance.audit.failed', [
                'error' => $e->getMessage(),
                'occurred_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $ex) {
            Log::error('Failed to publish audit failure event', ['error' => $ex->getMessage()]);
        }
    }
}
