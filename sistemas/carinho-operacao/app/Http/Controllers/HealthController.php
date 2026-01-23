<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Controller para health checks e status.
 */
class HealthController extends Controller
{
    /**
     * Health check basico.
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'carinho-operacao',
            'version' => config('app.version', '1.0.0'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Status detalhado do sistema.
     */
    public function status(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $healthy = collect($checks)->every(fn($check) => $check['healthy']);

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'service' => 'carinho-operacao',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    /**
     * Verifica conexao com banco de dados.
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');

            return [
                'healthy' => true,
                'message' => 'Database connection OK',
            ];
        } catch (\Throwable $e) {
            return [
                'healthy' => false,
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica conexao com cache.
     */
    protected function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, 'ok', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            if ($value !== 'ok') {
                throw new \RuntimeException('Cache read/write failed');
            }

            return [
                'healthy' => true,
                'message' => 'Cache connection OK',
            ];
        } catch (\Throwable $e) {
            return [
                'healthy' => false,
                'message' => 'Cache connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Informacoes do sistema.
     */
    public function info(): JsonResponse
    {
        return response()->json([
            'service' => 'carinho-operacao',
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ]);
    }
}
