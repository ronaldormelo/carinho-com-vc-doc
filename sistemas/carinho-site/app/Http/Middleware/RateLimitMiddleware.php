<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para rate limiting customizado.
 */
class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type = 'api'): Response
    {
        $key = $this->resolveRateLimitKey($request, $type);
        $maxAttempts = $this->getMaxAttempts($type);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'Muitas requisicoes. Tente novamente em ' . $retryAfter . ' segundos.',
                'retry_after' => $retryAfter,
            ], 429);
        }

        RateLimiter::hit($key);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, $maxAttempts));

        return $response;
    }

    /**
     * Resolve a chave de rate limit.
     */
    private function resolveRateLimitKey(Request $request, string $type): string
    {
        return $type . ':' . ($request->ip() ?? 'unknown');
    }

    /**
     * Obtem maximo de tentativas por tipo.
     */
    private function getMaxAttempts(string $type): int
    {
        return match ($type) {
            'forms' => config('integrations.rate_limit.forms_per_minute', 5),
            'api' => config('integrations.rate_limit.api_per_minute', 60),
            default => 60,
        };
    }
}
