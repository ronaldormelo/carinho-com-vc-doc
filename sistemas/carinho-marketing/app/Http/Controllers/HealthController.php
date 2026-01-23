<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    /**
     * Verifica saude do sistema.
     */
    public function show(): JsonResponse
    {
        $checks = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'service' => 'carinho-marketing',
            'version' => config('app.version', '1.0.0'),
            'checks' => [],
        ];

        // Verifica banco de dados
        try {
            DB::connection()->getPdo();
            $checks['checks']['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['checks']['database'] = 'error';
            $checks['status'] = 'degraded';
        }

        // Verifica cache/Redis
        try {
            Cache::store('redis')->put('health_check', 'ok', 10);
            $checks['checks']['cache'] = 'ok';
        } catch (\Throwable $e) {
            $checks['checks']['cache'] = 'error';
            $checks['status'] = 'degraded';
        }

        $statusCode = $checks['status'] === 'ok' ? 200 : 503;

        return response()->json($checks, $statusCode);
    }
}
