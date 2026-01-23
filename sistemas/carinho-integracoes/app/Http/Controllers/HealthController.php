<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use App\Services\Integrations\WhatsApp\ZApiClient;
use App\Models\IntegrationEvent;
use App\Models\RetryQueue;
use App\Models\DeadLetter;
use App\Models\SyncJob;

/**
 * Controller para health checks e status do sistema.
 */
class HealthController extends Controller
{
    /**
     * Health check basico.
     *
     * GET /health
     */
    public function check(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Health check detalhado.
     *
     * GET /health/detailed
     */
    public function detailed(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // Database
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
            $healthy = false;
        }

        // Redis
        try {
            Redis::ping();
            $checks['redis'] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            $checks['redis'] = ['status' => 'error', 'message' => $e->getMessage()];
            $healthy = false;
        }

        // Queue
        try {
            $queueSize = Queue::size('integrations');
            $checks['queue'] = [
                'status' => 'ok',
                'size' => $queueSize,
            ];
        } catch (\Throwable $e) {
            $checks['queue'] = ['status' => 'error', 'message' => $e->getMessage()];
            $healthy = false;
        }

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /**
     * Status completo do sistema.
     *
     * GET /status
     */
    public function status(ZApiClient $zapi): JsonResponse
    {
        // Metricas de eventos
        $eventStats = [
            'pending' => IntegrationEvent::pending()->count(),
            'today_processed' => IntegrationEvent::where('status_id', 3)
                ->whereDate('updated_at', today())
                ->count(),
            'today_failed' => IntegrationEvent::where('status_id', 4)
                ->whereDate('updated_at', today())
                ->count(),
        ];

        // Retry queue
        $retryStats = [
            'total' => RetryQueue::count(),
            'ready' => RetryQueue::readyForRetry()->count(),
        ];

        // Dead letter queue
        $dlqStats = DeadLetter::getStats();

        // Sync jobs
        $syncStats = SyncJob::getStats();

        // WhatsApp status
        $whatsappStatus = null;
        if (config('integrations.whatsapp.enabled')) {
            try {
                $status = $zapi->getInstanceStatus();
                $whatsappStatus = [
                    'connected' => $status['ok'] && data_get($status, 'body.connected', false),
                    'status' => data_get($status, 'body.status', 'unknown'),
                ];
            } catch (\Throwable $e) {
                $whatsappStatus = [
                    'connected' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'debug' => config('app.debug'),
            ],
            'timestamp' => now()->toIso8601String(),
            'events' => $eventStats,
            'retry_queue' => $retryStats,
            'dead_letter' => $dlqStats,
            'sync_jobs' => $syncStats,
            'whatsapp' => $whatsappStatus,
        ]);
    }
}
