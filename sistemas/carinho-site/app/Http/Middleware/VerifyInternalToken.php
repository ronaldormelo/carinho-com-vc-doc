<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar token de API interno.
 */
class VerifyInternalToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('health') || $request->is('api/health')) {
            return $next($request);
        }

        $token = $request->bearerToken()
            ?? $request->header('X-Internal-Token')
            ?? $request->header('X-API-Key');
        $expectedToken = config('integrations.internal_token');

        if (!$expectedToken) {
            // Se nao configurado, aceita (desenvolvimento)
            return $next($request);
        }

        if (!$token || !hash_equals($expectedToken, $token)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API token',
            ], 401);
        }

        return $next($request);
    }
}
