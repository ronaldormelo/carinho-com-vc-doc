<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * Middleware para logging de requisicoes.
 *
 * Registra todas as requisicoes para auditoria.
 */
class LogRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = (microtime(true) - $startTime) * 1000; // ms

        // Log apenas se habilitado
        if (config('integrations.logging.events', true)) {
            $apiKey = $request->attributes->get('api_key');

            Log::info('API Request', [
                'method' => $request->method(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'duration_ms' => round($duration, 2),
                'client' => $apiKey?->name ?? 'anonymous',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $response;
    }
}
