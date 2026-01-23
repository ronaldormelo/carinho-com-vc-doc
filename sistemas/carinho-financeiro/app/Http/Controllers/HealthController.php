<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * Health check básico.
     */
    public function index()
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'carinho-financeiro',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Health check detalhado.
     */
    public function detailed()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
        ];

        $allHealthy = collect($checks)->every(fn ($check) => $check['healthy']);

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'degraded',
            'service' => 'carinho-financeiro',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Verifica conexão com banco de dados.
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['healthy' => true, 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verifica conexão com Redis.
     */
    protected function checkRedis(): array
    {
        try {
            Redis::ping();
            return ['healthy' => true, 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => $e->getMessage()];
        }
    }
}
