<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use App\Services\Integrations\WhatsApp\ZApiClient;
use App\Services\IntegrationMonitor;
use App\Services\CircuitBreaker;
use App\Models\IntegrationEvent;
use App\Models\RetryQueue;
use App\Models\DeadLetter;
use App\Models\SyncJob;

/**
 * Controller para health checks, status e monitoramento do sistema.
 *
 * Práticas consolidadas:
 * - Health checks em camadas (básico, detalhado, completo)
 * - Dashboard operacional consolidado
 * - Alertas e métricas acionáveis
 */
class HealthController extends Controller
{
    /**
     * Health check basico.
     *
     * GET /health
     *
     * Retorno rápido para load balancers e monitoramento simples.
     */
    public function check(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Health check detalhado com dependências.
     *
     * GET /health/detailed
     *
     * Verifica banco de dados, Redis e filas.
     */
    public function detailed(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // Database
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $latency = (microtime(true) - $start) * 1000;

            $checks['database'] = [
                'status' => 'ok',
                'latency_ms' => round($latency, 2),
            ];
        } catch (\Throwable $e) {
            $checks['database'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
            $healthy = false;
        }

        // Redis
        try {
            $start = microtime(true);
            Redis::ping();
            $latency = (microtime(true) - $start) * 1000;

            $checks['redis'] = [
                'status' => 'ok',
                'latency_ms' => round($latency, 2),
            ];
        } catch (\Throwable $e) {
            $checks['redis'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
            $healthy = false;
        }

        // Queue
        try {
            $queueSizes = [
                'integrations-high' => Queue::size('integrations-high'),
                'integrations' => Queue::size('integrations'),
                'notifications' => Queue::size('notifications'),
                'integrations-low' => Queue::size('integrations-low'),
                'integrations-retry' => Queue::size('integrations-retry'),
            ];

            $checks['queues'] = [
                'status' => 'ok',
                'sizes' => $queueSizes,
                'total' => array_sum($queueSizes),
            ];
        } catch (\Throwable $e) {
            $checks['queues'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
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
     *
     * Métricas detalhadas de eventos, filas, sync e WhatsApp.
     */
    public function status(ZApiClient $zapi, CircuitBreaker $circuitBreaker): JsonResponse
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

        // Circuit breakers
        $circuitBreakers = $circuitBreaker->getAllStatus();

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
            'circuit_breakers' => $circuitBreakers,
        ]);
    }

    /**
     * Dashboard operacional consolidado.
     *
     * GET /dashboard
     *
     * Visão completa para operação: saúde, métricas, alertas.
     */
    public function dashboard(IntegrationMonitor $monitor): JsonResponse
    {
        return response()->json($monitor->getDashboard());
    }

    /**
     * Alertas ativos do sistema.
     *
     * GET /alerts
     *
     * Lista alertas que requerem atenção.
     */
    public function alerts(IntegrationMonitor $monitor): JsonResponse
    {
        $alerts = $monitor->checkAlerts();

        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'total' => count($alerts),
            'critical' => count(array_filter($alerts, fn($a) => $a['level'] === 'critical')),
            'warning' => count(array_filter($alerts, fn($a) => $a['level'] === 'warning')),
            'alerts' => $alerts,
        ]);
    }

    /**
     * Relatório diário.
     *
     * GET /report/daily
     *
     * Resumo das operações do dia anterior.
     */
    public function dailyReport(IntegrationMonitor $monitor): JsonResponse
    {
        return response()->json($monitor->getDailyReport());
    }

    /**
     * Status dos circuit breakers.
     *
     * GET /circuit-breakers
     */
    public function circuitBreakers(CircuitBreaker $circuitBreaker): JsonResponse
    {
        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'services' => $circuitBreaker->getAllStatus(),
        ]);
    }

    /**
     * Reset manual de circuit breaker.
     *
     * POST /circuit-breakers/{service}/reset
     */
    public function resetCircuitBreaker(string $service, CircuitBreaker $circuitBreaker): JsonResponse
    {
        $allowedServices = ['crm', 'operacao', 'financeiro', 'cuidadores', 'atendimento', 'marketing', 'site', 'documentos', 'whatsapp'];

        if (!in_array($service, $allowedServices)) {
            return response()->json([
                'error' => 'Invalid service',
                'allowed' => $allowedServices,
            ], 400);
        }

        $circuitBreaker->reset($service);

        return response()->json([
            'message' => "Circuit breaker for {$service} has been reset",
            'status' => $circuitBreaker->getStatus($service),
        ]);
    }
}
