<?php

namespace App\Http\Middleware;

use App\Models\RateLimit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * Middleware de rate limiting por cliente.
 *
 * Limita requisicoes por minuto baseado na API Key ou IP.
 */
class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Identifica cliente pela API Key ou IP
        $apiKey = $request->attributes->get('api_key');
        $clientId = $apiKey ? $apiKey->id : crc32($request->ip());

        // Verifica se excedeu limite
        if (RateLimit::isExceeded($clientId)) {
            $remaining = RateLimit::remaining($clientId);

            Log::warning('Rate limit exceeded', [
                'client' => $apiKey?->name ?? $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Too many requests',
                'retry_after' => 60 - now()->second,
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => config('integrations.rate_limit.per_minute', 60),
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => now()->addMinute()->startOfMinute()->timestamp,
            ]);
        }

        // Incrementa contador
        RateLimit::increment($clientId);

        // Adiciona headers de rate limit
        $response = $next($request);

        $limit = config('integrations.rate_limit.per_minute', 60);
        $remaining = RateLimit::remaining($clientId);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => max(0, $remaining - 1),
            'X-RateLimit-Reset' => now()->addMinute()->startOfMinute()->timestamp,
        ]);
    }
}
