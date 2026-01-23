<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificacao de token interno entre sistemas.
 */
class VerifyInternalToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken()
            ?? $request->header('X-Internal-Token')
            ?? $request->query('token');

        $expectedToken = config('integrations.internal.token');

        // Em ambiente de desenvolvimento, permite acesso sem token
        if (app()->environment('local', 'testing') && !$expectedToken) {
            return $next($request);
        }

        if (!$token || !$expectedToken) {
            return response()->json([
                'ok' => false,
                'message' => 'Token de autenticacao ausente',
            ], 401);
        }

        if (!hash_equals($expectedToken, $token)) {
            return response()->json([
                'ok' => false,
                'message' => 'Token de autenticacao invalido',
            ], 401);
        }

        return $next($request);
    }
}
