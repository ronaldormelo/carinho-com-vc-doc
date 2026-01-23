<?php

namespace App\Jobs;

use App\Integrations\Crm\CrmClient;
use App\Integrations\Integracoes\IntegracoesClient;
use App\Models\DataRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para verificar e alertar sobre prazos LGPD.
 *
 * Conforme LGPD, as solicitacoes devem ser atendidas em ate 15 dias.
 * Este job verifica:
 * - Solicitacoes vencidas (prazo ultrapassado)
 * - Solicitacoes proximas do vencimento (3 dias ou menos)
 * - Gera alertas para gestao e operacao
 */
class CheckLgpdDeadlines implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300; // 5 minutos

    // Dias de antecedencia para alerta
    private const WARNING_DAYS = 3;

    // Dias para alerta critico
    private const CRITICAL_DAYS = 1;

    public function handle(CrmClient $crm, IntegracoesClient $integracoes): void
    {
        Log::info('Starting LGPD deadlines check');

        $summary = [
            'overdue' => [],
            'critical' => [],
            'warning' => [],
            'total_pending' => 0,
        ];

        // Busca solicitacoes pendentes
        $pendingRequests = DataRequest::pending()
            ->with(['subjectType', 'requestType'])
            ->get();

        $summary['total_pending'] = $pendingRequests->count();

        foreach ($pendingRequests as $request) {
            $daysRemaining = $request->daysUntilDeadline();

            $requestData = [
                'id' => $request->id,
                'subject_type' => $request->subjectType->code,
                'subject_id' => $request->subject_id,
                'request_type' => $request->requestType->code,
                'requested_at' => $request->requested_at->toIso8601String(),
                'days_remaining' => $daysRemaining,
            ];

            if ($request->isOverdue()) {
                $summary['overdue'][] = $requestData;
            } elseif ($daysRemaining <= self::CRITICAL_DAYS) {
                $summary['critical'][] = $requestData;
            } elseif ($daysRemaining <= self::WARNING_DAYS) {
                $summary['warning'][] = $requestData;
            }
        }

        // Gera alertas se houver problemas
        $this->processAlerts($summary, $crm, $integracoes);

        Log::info('LGPD deadlines check completed', [
            'total_pending' => $summary['total_pending'],
            'overdue_count' => count($summary['overdue']),
            'critical_count' => count($summary['critical']),
            'warning_count' => count($summary['warning']),
        ]);
    }

    private function processAlerts(array $summary, CrmClient $crm, IntegracoesClient $integracoes): void
    {
        // Alertas de solicitacoes vencidas (CRITICO)
        if (!empty($summary['overdue'])) {
            $this->sendOverdueAlert($summary['overdue'], $crm, $integracoes);
        }

        // Alertas de solicitacoes criticas (1 dia ou menos)
        if (!empty($summary['critical'])) {
            $this->sendCriticalAlert($summary['critical'], $crm, $integracoes);
        }

        // Alertas de aviso (3 dias ou menos)
        if (!empty($summary['warning'])) {
            $this->sendWarningAlert($summary['warning'], $crm, $integracoes);
        }

        // Publica evento consolidado
        $this->publishDeadlineEvent($summary, $integracoes);
    }

    private function sendOverdueAlert(array $requests, CrmClient $crm, IntegracoesClient $integracoes): void
    {
        $message = sprintf(
            '[URGENTE] %d solicitacao(es) LGPD vencida(s)! Prazo legal de 15 dias ultrapassado. Acao imediata necessaria.',
            count($requests)
        );

        Log::error('LGPD requests overdue', [
            'count' => count($requests),
            'requests' => $requests,
        ]);

        // Notifica CRM
        try {
            $crm->createAlert([
                'type' => 'lgpd_overdue',
                'severity' => 'critical',
                'message' => $message,
                'data' => $requests,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send CRM alert', ['error' => $e->getMessage()]);
        }

        // Publica evento no hub
        try {
            $integracoes->publishEvent('lgpd.deadline.overdue', [
                'count' => count($requests),
                'requests' => $requests,
                'severity' => 'critical',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to publish event', ['error' => $e->getMessage()]);
        }
    }

    private function sendCriticalAlert(array $requests, CrmClient $crm, IntegracoesClient $integracoes): void
    {
        $message = sprintf(
            '[CRITICO] %d solicitacao(es) LGPD vencendo em 24 horas ou menos. Prioridade maxima.',
            count($requests)
        );

        Log::warning('LGPD requests critical deadline', [
            'count' => count($requests),
            'requests' => $requests,
        ]);

        try {
            $crm->createAlert([
                'type' => 'lgpd_critical',
                'severity' => 'high',
                'message' => $message,
                'data' => $requests,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send CRM alert', ['error' => $e->getMessage()]);
        }

        try {
            $integracoes->publishEvent('lgpd.deadline.critical', [
                'count' => count($requests),
                'requests' => $requests,
                'severity' => 'high',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to publish event', ['error' => $e->getMessage()]);
        }
    }

    private function sendWarningAlert(array $requests, CrmClient $crm, IntegracoesClient $integracoes): void
    {
        $message = sprintf(
            '[ATENCAO] %d solicitacao(es) LGPD vencendo em 3 dias ou menos.',
            count($requests)
        );

        Log::info('LGPD requests approaching deadline', [
            'count' => count($requests),
            'requests' => $requests,
        ]);

        try {
            $crm->createAlert([
                'type' => 'lgpd_warning',
                'severity' => 'medium',
                'message' => $message,
                'data' => $requests,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send CRM alert', ['error' => $e->getMessage()]);
        }
    }

    private function publishDeadlineEvent(array $summary, IntegracoesClient $integracoes): void
    {
        try {
            $integracoes->publishEvent('lgpd.deadline.check', [
                'total_pending' => $summary['total_pending'],
                'overdue_count' => count($summary['overdue']),
                'critical_count' => count($summary['critical']),
                'warning_count' => count($summary['warning']),
                'checked_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to publish deadline check event', ['error' => $e->getMessage()]);
        }
    }
}
