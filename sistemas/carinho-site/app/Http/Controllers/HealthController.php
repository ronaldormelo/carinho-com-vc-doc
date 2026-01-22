<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Controller para health checks.
 */
class HealthController extends Controller
{
    /**
     * Health check basico.
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
     */
    public function detailed(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // Check Database
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'ok',
                'latency_ms' => $this->measureDbLatency(),
            ];
        } catch (\Exception $e) {
            $checks['database'] = [
                'status' => 'error',
                'message' => 'Database connection failed',
            ];
            $healthy = false;
        }

        // Check Redis
        try {
            Redis::ping();
            $checks['redis'] = ['status' => 'ok'];
        } catch (\Exception $e) {
            $checks['redis'] = [
                'status' => 'error',
                'message' => 'Redis connection failed',
            ];
            $healthy = false;
        }

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
        ], $healthy ? 200 : 503);
    }

    /**
     * Mede latencia do banco.
     */
    private function measureDbLatency(): float
    {
        $start = microtime(true);
        DB::select('SELECT 1');
        return round((microtime(true) - $start) * 1000, 2);
    }
}
