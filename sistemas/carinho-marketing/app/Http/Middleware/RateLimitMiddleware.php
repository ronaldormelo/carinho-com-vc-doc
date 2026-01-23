<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Limite de requisicoes por minuto.
     */
    private int $maxAttempts = 60;

    /**
     * Janela de tempo em segundos.
     */
    private int $decaySeconds = 60;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?int $maxAttempts = null): Response
    {
        $maxAttempts = $maxAttempts ?? $this->maxAttempts;
        $key = $this->resolveRequestSignature($request);

        $attempts = (int) Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $this->decaySeconds,
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'Retry-After' => $this->decaySeconds,
            ]);
        }

        Cache::put($key, $attempts + 1, $this->decaySeconds);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $maxAttempts - $attempts - 1),
        ]);
    }

    /**
     * Resolve the request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $identifier = $request->header('X-Internal-Token')
            ?? $request->ip();

        return 'rate_limit:' . sha1($identifier . '|' . $request->path());
    }
}
