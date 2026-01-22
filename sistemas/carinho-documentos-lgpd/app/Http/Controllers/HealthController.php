<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Health check detalhado.
     */
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $healthy = !in_array(false, array_column($checks, 'ok'));

        return response()->json([
            'ok' => $healthy,
            'service' => 'carinho-documentos-lgpd',
            'version' => config('app.version', '1.0.0'),
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /**
     * Health check simples para load balancer.
     */
    public function up(): JsonResponse
    {
        return response()->json(['status' => 'up']);
    }

    /**
     * Verifica conexao com banco de dados.
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['ok' => true, 'message' => 'Connected'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verifica conexao com cache.
     */
    private function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, 'ok', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            return ['ok' => $value === 'ok', 'message' => 'Connected'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verifica configuracao de storage.
     */
    private function checkStorage(): array
    {
        try {
            $configured = !empty(config('integrations.aws.key'))
                && !empty(config('integrations.aws.secret'))
                && !empty(config('integrations.aws.bucket'));

            return [
                'ok' => $configured,
                'message' => $configured ? 'Configured' : 'Not configured',
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
