<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyInternalToken
{
    /**
     * Verifica token de autenticação interna entre sistemas.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->header('X-Internal-Token');
        $expectedToken = config('integrations.internal.token');

        if (!$expectedToken) {
            return response()->json([
                'error' => 'Internal authentication not configured',
            ], 500);
        }

        if (!$token || !hash_equals($expectedToken, $token)) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 401);
        }

        return $next($request);
    }
}
