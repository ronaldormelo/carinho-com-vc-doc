<?php

namespace App\Services;

use App\Models\IntegrationEvent;
use App\Models\RetryQueue;
use App\Models\DeadLetter;
use App\Models\SyncJob;
use App\Models\WebhookDelivery;
use App\Services\Integrations\WhatsApp\ZApiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Serviço de monitoramento de integrações.
 *
 * Fornece visão consolidada do estado das integrações,
 * métricas de desempenho e alertas operacionais.
 *
 * Práticas consolidadas:
 * - Métricas simples e acionáveis
 * - Alertas com thresholds configuráveis
 * - Verificação periódica de dependências
 */
class IntegrationMonitor
{
    private CircuitBreaker $circuitBreaker;
    private array $alertThresholds;

    public function __construct(CircuitBreaker $circuitBreaker)
    {
        $this->circuitBreaker = $circuitBreaker;
        $this->alertThresholds = [
            'pending_events' => (int) env('ALERT_PENDING_EVENTS', 100),
            'retry_queue' => (int) env('ALERT_RETRY_QUEUE', 50),
            'dead_letter' => (int) env('ALERT_DEAD_LETTER', 10),
            'error_rate_percent' => (int) env('ALERT_ERROR_RATE', 5),
            'sync_failures' => (int) env('ALERT_SYNC_FAILURES', 2),
        ];
    }

    /**
     * Verifica saúde de todas as dependências.
     */
    public function checkDependencies(): array
    {
        $dependencies = [];

        // Database
        try {
            DB::connection()->getPdo();
            $dependencies['database'] = [
                'status' => 'ok',
                'latency_ms' => $this->measureDbLatency(),
            ];
        } catch (\Throwable $e) {
            $dependencies['database'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        // Redis
        try {
            $start = microtime(true);
            Redis::ping();
            $latency = (microtime(true) - $start) * 1000;

            $dependencies['redis'] = [
                'status' => 'ok',
                'latency_ms' => round($latency, 2),
            ];
        } catch (\Throwable $e) {
            $dependencies['redis'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        // WhatsApp (Z-API)
        if (config('integrations.whatsapp.enabled')) {
            try {
                $zapi = app(ZApiClient::class);
                $connected = $zapi->isConnected();

                $dependencies['whatsapp'] = [
                    'status' => $connected ? 'ok' : 'warning',
                    'connected' => $connected,
                ];

                if (!$connected) {
                    Log::warning('WhatsApp not connected');
                }
            } catch (\Throwable $e) {
                $dependencies['whatsapp'] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $dependencies;
    }

    /**
     * Obtém métricas de eventos.
     */
    public function getEventMetrics(): array
    {
        $today = today();

        return [
            'pending' => IntegrationEvent::pending()->count(),
            'today' => [
                'total' => IntegrationEvent::whereDate('created_at', $today)->count(),
                'processed' => IntegrationEvent::where('status_id', 3)
                    ->whereDate('updated_at', $today)
                    ->count(),
                'failed' => IntegrationEvent::where('status_id', 4)
                    ->whereDate('updated_at', $today)
                    ->count(),
            ],
            'by_type' => $this->getEventsByType($today),
            'by_source' => $this->getEventsBySource($today),
        ];
    }

    /**
     * Obtém métricas de filas.
     */
    public function getQueueMetrics(): array
    {
        return [
            'retry_queue' => [
                'total' => RetryQueue::count(),
                'ready' => RetryQueue::readyForRetry()->count(),
                'oldest' => RetryQueue::orderBy('created_at')->value('created_at'),
            ],
            'dead_letter' => [
                'total' => DeadLetter::count(),
                'today' => DeadLetter::whereDate('created_at', today())->count(),
                'by_event_type' => DeadLetter::getStatsByEventType(),
            ],
        ];
    }

    /**
     * Obtém métricas de sincronização.
     */
    public function getSyncMetrics(): array
    {
        $lastJobs = SyncJob::orderBy('id', 'desc')
            ->limit(20)
            ->get()
            ->groupBy('job_type');

        $metrics = [];

        foreach ($lastJobs as $type => $jobs) {
            $lastJob = $jobs->first();
            $failedCount = $jobs->where('status_id', 4)->count();

            $metrics[$type] = [
                'last_run' => $lastJob->started_at,
                'last_status' => $lastJob->status?->code ?? 'unknown',
                'recent_failures' => $failedCount,
                'duration_seconds' => $lastJob->finished_at && $lastJob->started_at
                    ? $lastJob->finished_at->diffInSeconds($lastJob->started_at)
                    : null,
            ];
        }

        return $metrics;
    }

    /**
     * Obtém métricas de entregas de webhook.
     */
    public function getDeliveryMetrics(): array
    {
        $today = today();

        return [
            'today' => [
                'total' => WebhookDelivery::whereDate('last_attempt_at', $today)->count(),
                'sent' => WebhookDelivery::where('status_id', 2)
                    ->whereDate('last_attempt_at', $today)
                    ->count(),
                'failed' => WebhookDelivery::where('status_id', 3)
                    ->whereDate('last_attempt_at', $today)
                    ->count(),
            ],
            'by_endpoint' => $this->getDeliveriesByEndpoint($today),
        ];
    }

    /**
     * Verifica alertas ativos.
     */
    public function checkAlerts(): array
    {
        $alerts = [];

        // Eventos pendentes
        $pendingCount = IntegrationEvent::pending()->count();
        if ($pendingCount > $this->alertThresholds['pending_events']) {
            $alerts[] = [
                'level' => $pendingCount > $this->alertThresholds['pending_events'] * 5 ? 'critical' : 'warning',
                'type' => 'pending_events',
                'message' => "Alto número de eventos pendentes: {$pendingCount}",
                'value' => $pendingCount,
                'threshold' => $this->alertThresholds['pending_events'],
            ];
        }

        // Retry queue
        $retryCount = RetryQueue::count();
        if ($retryCount > $this->alertThresholds['retry_queue']) {
            $alerts[] = [
                'level' => $retryCount > $this->alertThresholds['retry_queue'] * 4 ? 'critical' : 'warning',
                'type' => 'retry_queue',
                'message' => "Retry queue elevada: {$retryCount} itens",
                'value' => $retryCount,
                'threshold' => $this->alertThresholds['retry_queue'],
            ];
        }

        // Dead Letter Queue
        $dlqCount = DeadLetter::count();
        if ($dlqCount > $this->alertThresholds['dead_letter']) {
            $alerts[] = [
                'level' => $dlqCount > $this->alertThresholds['dead_letter'] * 5 ? 'critical' : 'warning',
                'type' => 'dead_letter',
                'message' => "Dead Letter Queue com {$dlqCount} itens para revisão",
                'value' => $dlqCount,
                'threshold' => $this->alertThresholds['dead_letter'],
            ];
        }

        // Taxa de erro
        $errorRate = $this->calculateErrorRate();
        if ($errorRate > $this->alertThresholds['error_rate_percent']) {
            $alerts[] = [
                'level' => $errorRate > $this->alertThresholds['error_rate_percent'] * 3 ? 'critical' : 'warning',
                'type' => 'error_rate',
                'message' => "Taxa de erro elevada: {$errorRate}%",
                'value' => $errorRate,
                'threshold' => $this->alertThresholds['error_rate_percent'],
            ];
        }

        // Sync jobs falhando
        $syncFailures = $this->countRecentSyncFailures();
        if ($syncFailures > $this->alertThresholds['sync_failures']) {
            $alerts[] = [
                'level' => 'warning',
                'type' => 'sync_failures',
                'message' => "Sync jobs com {$syncFailures} falhas recentes",
                'value' => $syncFailures,
                'threshold' => $this->alertThresholds['sync_failures'],
            ];
        }

        // Circuit breakers abertos
        $openCircuits = $this->getOpenCircuits();
        if (!empty($openCircuits)) {
            $alerts[] = [
                'level' => 'critical',
                'type' => 'circuit_breaker',
                'message' => 'Circuit breakers abertos: ' . implode(', ', $openCircuits),
                'services' => $openCircuits,
            ];
        }

        return $alerts;
    }

    /**
     * Gera relatório diário consolidado.
     */
    public function getDailyReport(): array
    {
        $yesterday = today()->subDay();

        return [
            'date' => $yesterday->format('Y-m-d'),
            'events' => [
                'total' => IntegrationEvent::whereDate('created_at', $yesterday)->count(),
                'processed' => IntegrationEvent::where('status_id', 3)
                    ->whereDate('updated_at', $yesterday)
                    ->count(),
                'failed' => IntegrationEvent::where('status_id', 4)
                    ->whereDate('updated_at', $yesterday)
                    ->count(),
            ],
            'deliveries' => [
                'total' => WebhookDelivery::whereDate('last_attempt_at', $yesterday)->count(),
                'success' => WebhookDelivery::where('status_id', 2)
                    ->whereDate('last_attempt_at', $yesterday)
                    ->count(),
            ],
            'sync_jobs' => SyncJob::whereDate('started_at', $yesterday)
                ->select('job_type', 'status_id')
                ->get()
                ->groupBy('job_type')
                ->map(fn($jobs) => [
                    'total' => $jobs->count(),
                    'success' => $jobs->where('status_id', 3)->count(),
                ])
                ->toArray(),
            'dead_letter_added' => DeadLetter::whereDate('created_at', $yesterday)->count(),
        ];
    }

    /**
     * Obtém dashboard operacional.
     */
    public function getDashboard(): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'health' => $this->getOverallHealth(),
            'dependencies' => $this->checkDependencies(),
            'events' => $this->getEventMetrics(),
            'queues' => $this->getQueueMetrics(),
            'deliveries' => $this->getDeliveryMetrics(),
            'sync' => $this->getSyncMetrics(),
            'circuit_breakers' => $this->circuitBreaker->getAllStatus(),
            'alerts' => $this->checkAlerts(),
        ];
    }

    /**
     * Calcula saúde geral do sistema.
     */
    private function getOverallHealth(): array
    {
        $alerts = $this->checkAlerts();
        $criticalCount = count(array_filter($alerts, fn($a) => $a['level'] === 'critical'));
        $warningCount = count(array_filter($alerts, fn($a) => $a['level'] === 'warning'));

        if ($criticalCount > 0) {
            $status = 'critical';
        } elseif ($warningCount > 0) {
            $status = 'degraded';
        } else {
            $status = 'healthy';
        }

        return [
            'status' => $status,
            'critical_alerts' => $criticalCount,
            'warning_alerts' => $warningCount,
        ];
    }

    /**
     * Mede latência do banco de dados.
     */
    private function measureDbLatency(): float
    {
        $start = microtime(true);
        DB::select('SELECT 1');
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Calcula taxa de erro das últimas 24h.
     */
    private function calculateErrorRate(): float
    {
        $since = now()->subDay();

        $total = IntegrationEvent::where('created_at', '>=', $since)->count();

        if ($total === 0) {
            return 0;
        }

        $failed = IntegrationEvent::where('status_id', 4)
            ->where('created_at', '>=', $since)
            ->count();

        return round(($failed / $total) * 100, 2);
    }

    /**
     * Conta falhas recentes de sync jobs.
     */
    private function countRecentSyncFailures(): int
    {
        return SyncJob::where('status_id', 4)
            ->where('finished_at', '>=', now()->subDay())
            ->count();
    }

    /**
     * Obtém circuit breakers abertos.
     */
    private function getOpenCircuits(): array
    {
        $services = ['crm', 'operacao', 'financeiro', 'cuidadores', 'atendimento', 'marketing', 'whatsapp'];
        $open = [];

        foreach ($services as $service) {
            if ($this->circuitBreaker->isOpen($service)) {
                $open[] = $service;
            }
        }

        return $open;
    }

    /**
     * Obtém eventos por tipo.
     */
    private function getEventsByType($date): array
    {
        return IntegrationEvent::whereDate('created_at', $date)
            ->select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();
    }

    /**
     * Obtém eventos por origem.
     */
    private function getEventsBySource($date): array
    {
        return IntegrationEvent::whereDate('created_at', $date)
            ->select('source_system', DB::raw('COUNT(*) as count'))
            ->groupBy('source_system')
            ->pluck('count', 'source_system')
            ->toArray();
    }

    /**
     * Obtém entregas por endpoint.
     */
    private function getDeliveriesByEndpoint($date): array
    {
        return WebhookDelivery::whereDate('last_attempt_at', $date)
            ->join('webhook_endpoints', 'webhook_deliveries.endpoint_id', '=', 'webhook_endpoints.id')
            ->select('webhook_endpoints.system_name', DB::raw('COUNT(*) as total'))
            ->groupBy('webhook_endpoints.system_name')
            ->pluck('total', 'system_name')
            ->toArray();
    }
}
