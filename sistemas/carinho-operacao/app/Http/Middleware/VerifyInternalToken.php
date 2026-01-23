<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar token de comunicacao interna entre sistemas.
 */
class VerifyInternalToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('integrations.internal.token');

        if (!$token) {
            return $next($request);
        }

        $providedToken = $request->bearerToken()
            ?? $request->header('X-Internal-Token')
            ?? $request->query('token');

        if (!$providedToken || !hash_equals($token, $providedToken)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing internal token.',
            ], 401);
        }

        return $next($request);
    }
}
