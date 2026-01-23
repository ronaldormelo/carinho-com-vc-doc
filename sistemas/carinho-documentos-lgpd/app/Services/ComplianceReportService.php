<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\Consent;
use App\Models\DataRequest;
use App\Models\Document;
use App\Models\DomainDocumentStatus;
use App\Models\DomainRequestStatus;
use App\Models\RetentionPolicy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para geracao de relatorios de conformidade LGPD.
 *
 * Fornece visao consolidada do estado de conformidade, incluindo:
 * - Status de solicitacoes LGPD (pendentes, atrasadas, concluidas)
 * - Metricas de consentimentos
 * - Auditoria de acessos
 * - Politicas de retencao
 * - Indicadores de risco
 */
class ComplianceReportService
{
    /**
     * Gera relatorio completo de conformidade.
     */
    public function generateComplianceReport(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now();

        return [
            'report_info' => [
                'generated_at' => now()->toIso8601String(),
                'period' => [
                    'start' => $startDate->toIso8601String(),
                    'end' => $endDate->toIso8601String(),
                ],
                'generated_by' => config('branding.name', 'Carinho com Voce'),
            ],
            'lgpd_requests' => $this->getLgpdRequestsMetrics($startDate, $endDate),
            'consents' => $this->getConsentsMetrics($startDate, $endDate),
            'documents' => $this->getDocumentsMetrics($startDate, $endDate),
            'access_audit' => $this->getAccessAuditMetrics($startDate, $endDate),
            'retention_status' => $this->getRetentionStatus(),
            'compliance_score' => $this->calculateComplianceScore($startDate, $endDate),
            'risk_indicators' => $this->getRiskIndicators(),
            'recommendations' => $this->getRecommendations(),
        ];
    }

    /**
     * Gera relatorio resumido (dashboard).
     */
    public function generateDashboard(): array
    {
        return [
            'summary' => $this->getSummary(),
            'alerts' => $this->getActiveAlerts(),
            'recent_requests' => $this->getRecentRequests(10),
            'compliance_score' => $this->calculateComplianceScore(now()->startOfMonth(), now()),
        ];
    }

    /**
     * Metricas de solicitacoes LGPD.
     */
    private function getLgpdRequestsMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $requests = DataRequest::whereBetween('requested_at', [$startDate, $endDate]);

        $byStatus = DataRequest::whereBetween('requested_at', [$startDate, $endDate])
            ->select('status_id', DB::raw('count(*) as count'))
            ->groupBy('status_id')
            ->with('status')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->status->code => $item->count])
            ->toArray();

        $byType = DataRequest::whereBetween('requested_at', [$startDate, $endDate])
            ->select('request_type_id', DB::raw('count(*) as count'))
            ->groupBy('request_type_id')
            ->with('requestType')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->requestType->code => $item->count])
            ->toArray();

        // Tempo medio de atendimento
        $avgProcessingTime = DataRequest::whereBetween('requested_at', [$startDate, $endDate])
            ->where('status_id', DomainRequestStatus::DONE)
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(DATEDIFF(resolved_at, requested_at)) as avg_days')
            ->first();

        // Solicitacoes atrasadas
        $overdueCount = DataRequest::pending()
            ->where('requested_at', '<', now()->subDays(15))
            ->count();

        // Taxa de cumprimento de prazo
        $totalResolved = DataRequest::whereBetween('requested_at', [$startDate, $endDate])
            ->whereIn('status_id', [DomainRequestStatus::DONE, DomainRequestStatus::REJECTED])
            ->count();

        $resolvedOnTime = DataRequest::whereBetween('requested_at', [$startDate, $endDate])
            ->whereIn('status_id', [DomainRequestStatus::DONE, DomainRequestStatus::REJECTED])
            ->whereRaw('DATEDIFF(resolved_at, requested_at) <= 15')
            ->count();

        $complianceRate = $totalResolved > 0 ? round(($resolvedOnTime / $totalResolved) * 100, 2) : 100;

        return [
            'total' => $requests->count(),
            'by_status' => $byStatus,
            'by_type' => $byType,
            'overdue_count' => $overdueCount,
            'avg_processing_days' => round($avgProcessingTime->avg_days ?? 0, 1),
            'deadline_compliance_rate' => $complianceRate,
            'pending_count' => DataRequest::pending()->count(),
        ];
    }

    /**
     * Metricas de consentimentos.
     */
    private function getConsentsMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $granted = Consent::whereBetween('granted_at', [$startDate, $endDate])->count();
        $revoked = Consent::whereBetween('revoked_at', [$startDate, $endDate])->count();

        $byType = Consent::whereBetween('granted_at', [$startDate, $endDate])
            ->select('consent_type', DB::raw('count(*) as count'))
            ->groupBy('consent_type')
            ->get()
            ->pluck('count', 'consent_type')
            ->toArray();

        $bySource = Consent::whereBetween('granted_at', [$startDate, $endDate])
            ->select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->get()
            ->pluck('count', 'source')
            ->toArray();

        $totalActive = Consent::active()->count();

        // Revogacoes por motivo
        $revocationsByReason = Consent::whereBetween('revoked_at', [$startDate, $endDate])
            ->whereNotNull('revocation_reason')
            ->select('revocation_reason', DB::raw('count(*) as count'))
            ->groupBy('revocation_reason')
            ->get()
            ->pluck('count', 'revocation_reason')
            ->toArray();

        return [
            'granted' => $granted,
            'revoked' => $revoked,
            'total_active' => $totalActive,
            'by_type' => $byType,
            'by_source' => $bySource,
            'revocations_by_reason' => $revocationsByReason,
            'revocation_rate' => $granted > 0 ? round(($revoked / $granted) * 100, 2) : 0,
        ];
    }

    /**
     * Metricas de documentos.
     */
    private function getDocumentsMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $created = Document::whereBetween('created_at', [$startDate, $endDate])->count();

        $byStatus = Document::whereBetween('created_at', [$startDate, $endDate])
            ->select('status_id', DB::raw('count(*) as count'))
            ->groupBy('status_id')
            ->with('status')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->status->code => $item->count])
            ->toArray();

        $signed = Document::whereBetween('created_at', [$startDate, $endDate])
            ->where('status_id', DomainDocumentStatus::SIGNED)
            ->count();

        return [
            'created' => $created,
            'by_status' => $byStatus,
            'signed' => $signed,
            'signature_rate' => $created > 0 ? round(($signed / $created) * 100, 2) : 0,
            'total_active' => Document::whereIn('status_id', [
                DomainDocumentStatus::DRAFT,
                DomainDocumentStatus::SIGNED,
            ])->count(),
        ];
    }

    /**
     * Metricas de auditoria de acesso.
     */
    private function getAccessAuditMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $totalLogs = AccessLog::whereBetween('created_at', [$startDate, $endDate])->count();

        $byAction = AccessLog::whereBetween('created_at', [$startDate, $endDate])
            ->select('action_id', DB::raw('count(*) as count'))
            ->groupBy('action_id')
            ->with('action')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->action->code => $item->count])
            ->toArray();

        $uniqueActors = AccessLog::whereBetween('created_at', [$startDate, $endDate])
            ->distinct('actor_id')
            ->count();

        $uniqueDocuments = AccessLog::whereBetween('created_at', [$startDate, $endDate])
            ->distinct('document_id')
            ->count();

        return [
            'total_logs' => $totalLogs,
            'by_action' => $byAction,
            'unique_actors' => $uniqueActors,
            'unique_documents' => $uniqueDocuments,
            'log_retention_compliant' => $this->checkLogRetentionCompliance(),
        ];
    }

    /**
     * Status das politicas de retencao.
     */
    private function getRetentionStatus(): array
    {
        $policies = RetentionPolicy::with('docType')->get();

        $status = [];
        foreach ($policies as $policy) {
            $expiredCount = Document::whereHas('template', function ($query) use ($policy) {
                $query->where('doc_type_id', $policy->doc_type_id);
            })
                ->where('created_at', '<', now()->subDays($policy->retention_days))
                ->where('status_id', '!=', DomainDocumentStatus::ARCHIVED)
                ->count();

            $status[] = [
                'doc_type' => $policy->docType->code,
                'retention_days' => $policy->retention_days,
                'retention_years' => round($policy->retention_days / 365, 1),
                'expired_not_archived' => $expiredCount,
                'compliant' => $expiredCount === 0,
            ];
        }

        return $status;
    }

    /**
     * Calcula score de conformidade (0-100).
     */
    private function calculateComplianceScore(Carbon $startDate, Carbon $endDate): array
    {
        $scores = [];

        // 1. Prazo LGPD (peso 40%)
        $lgpdMetrics = $this->getLgpdRequestsMetrics($startDate, $endDate);
        $scores['lgpd_deadline'] = [
            'score' => $lgpdMetrics['deadline_compliance_rate'],
            'weight' => 40,
            'weighted_score' => ($lgpdMetrics['deadline_compliance_rate'] / 100) * 40,
        ];

        // 2. Retencao de documentos (peso 25%)
        $retentionStatus = $this->getRetentionStatus();
        $totalPolicies = count($retentionStatus);
        $compliantPolicies = collect($retentionStatus)->where('compliant', true)->count();
        $retentionScore = $totalPolicies > 0 ? ($compliantPolicies / $totalPolicies) * 100 : 100;

        $scores['retention'] = [
            'score' => round($retentionScore, 2),
            'weight' => 25,
            'weighted_score' => ($retentionScore / 100) * 25,
        ];

        // 3. Auditoria (peso 20%) - verificar se logs estao sendo registrados
        $auditMetrics = $this->getAccessAuditMetrics($startDate, $endDate);
        $auditScore = $auditMetrics['log_retention_compliant'] ? 100 : 50;

        $scores['audit'] = [
            'score' => $auditScore,
            'weight' => 20,
            'weighted_score' => ($auditScore / 100) * 20,
        ];

        // 4. Consentimentos (peso 15%) - verificar base legal
        $consentMetrics = $this->getConsentsMetrics($startDate, $endDate);
        $consentScore = $consentMetrics['total_active'] > 0 ? 100 : 80;

        $scores['consents'] = [
            'score' => $consentScore,
            'weight' => 15,
            'weighted_score' => ($consentScore / 100) * 15,
        ];

        // Score total
        $totalScore = array_sum(array_column($scores, 'weighted_score'));

        // Classificacao
        $classification = match (true) {
            $totalScore >= 90 => 'Excelente',
            $totalScore >= 75 => 'Bom',
            $totalScore >= 60 => 'Adequado',
            $totalScore >= 40 => 'Atencao',
            default => 'Critico',
        };

        return [
            'total_score' => round($totalScore, 2),
            'classification' => $classification,
            'breakdown' => $scores,
        ];
    }

    /**
     * Indicadores de risco.
     */
    private function getRiskIndicators(): array
    {
        $indicators = [];

        // Risco 1: Solicitacoes vencidas
        $overdueRequests = DataRequest::overdue()->count();
        if ($overdueRequests > 0) {
            $indicators[] = [
                'type' => 'lgpd_overdue',
                'severity' => 'critical',
                'description' => "{$overdueRequests} solicitacao(es) LGPD vencida(s)",
                'recommendation' => 'Processar imediatamente para evitar sancoes',
            ];
        }

        // Risco 2: Solicitacoes proximas do prazo
        $nearDeadline = DataRequest::pending()
            ->where('requested_at', '>=', now()->subDays(15))
            ->where('requested_at', '<', now()->subDays(12))
            ->count();
        if ($nearDeadline > 0) {
            $indicators[] = [
                'type' => 'lgpd_near_deadline',
                'severity' => 'high',
                'description' => "{$nearDeadline} solicitacao(es) proxima(s) do prazo",
                'recommendation' => 'Priorizar processamento',
            ];
        }

        // Risco 3: Documentos expirados nao arquivados
        $retentionStatus = $this->getRetentionStatus();
        $expiredDocs = collect($retentionStatus)->sum('expired_not_archived');
        if ($expiredDocs > 0) {
            $indicators[] = [
                'type' => 'retention_expired',
                'severity' => 'medium',
                'description' => "{$expiredDocs} documento(s) expirado(s) nao arquivado(s)",
                'recommendation' => 'Executar politica de retencao',
            ];
        }

        // Risco 4: Baixa taxa de assinatura
        $docMetrics = $this->getDocumentsMetrics(now()->startOfMonth(), now());
        if ($docMetrics['signature_rate'] < 50 && $docMetrics['created'] > 10) {
            $indicators[] = [
                'type' => 'low_signature_rate',
                'severity' => 'low',
                'description' => "Taxa de assinatura baixa: {$docMetrics['signature_rate']}%",
                'recommendation' => 'Verificar processo de assinatura',
            ];
        }

        return $indicators;
    }

    /**
     * Recomendacoes de melhoria.
     */
    private function getRecommendations(): array
    {
        $recommendations = [];
        $riskIndicators = $this->getRiskIndicators();

        if (empty($riskIndicators)) {
            $recommendations[] = [
                'type' => 'positive',
                'message' => 'Sistema em conformidade. Manter monitoramento regular.',
            ];
        } else {
            foreach ($riskIndicators as $risk) {
                $recommendations[] = [
                    'type' => 'improvement',
                    'priority' => $risk['severity'],
                    'message' => $risk['recommendation'],
                    'related_risk' => $risk['type'],
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Resumo executivo.
     */
    private function getSummary(): array
    {
        return [
            'pending_requests' => DataRequest::pending()->count(),
            'overdue_requests' => DataRequest::overdue()->count(),
            'active_consents' => Consent::active()->count(),
            'active_documents' => Document::whereIn('status_id', [
                DomainDocumentStatus::DRAFT,
                DomainDocumentStatus::SIGNED,
            ])->count(),
            'today_logs' => AccessLog::whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Alertas ativos.
     */
    private function getActiveAlerts(): array
    {
        $alerts = [];

        $overdueRequests = DataRequest::overdue()->count();
        if ($overdueRequests > 0) {
            $alerts[] = [
                'type' => 'critical',
                'message' => "{$overdueRequests} solicitacao(es) LGPD vencida(s)",
            ];
        }

        $criticalRequests = DataRequest::pending()
            ->where('requested_at', '>=', now()->subDays(15))
            ->where('requested_at', '<', now()->subDays(14))
            ->count();
        if ($criticalRequests > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$criticalRequests} solicitacao(es) vencendo em 24h",
            ];
        }

        return $alerts;
    }

    /**
     * Solicitacoes recentes.
     */
    private function getRecentRequests(int $limit): array
    {
        return DataRequest::with(['subjectType', 'requestType', 'status'])
            ->orderBy('requested_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'type' => $r->requestType->code,
                'status' => $r->status->code,
                'requested_at' => $r->requested_at->toIso8601String(),
                'days_remaining' => $r->daysUntilDeadline(),
            ])
            ->toArray();
    }

    /**
     * Verifica se a retencao de logs esta em conformidade.
     */
    private function checkLogRetentionCompliance(): bool
    {
        $retentionDays = config('documentos.audit.log_retention_days', 1825);

        // Verifica se existem logs muito antigos que deveriam ter sido limpos
        $oldLogs = AccessLog::where('created_at', '<', now()->subDays($retentionDays + 30))->exists();

        return !$oldLogs;
    }
}
