<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyInternalToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Internal-Token')
            ?? $request->header('Authorization');

        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }

        $expectedToken = config('integrations.internal.token');

        if (!$expectedToken || $token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autorizacao invalido',
            ], 401);
        }

        return $next($request);
    }
}
