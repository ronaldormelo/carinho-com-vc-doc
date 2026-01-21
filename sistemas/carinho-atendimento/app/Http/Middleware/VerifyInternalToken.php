<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyInternalToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('integrations.internal.token');

        if (!$expected) {
            return $next($request);
        }

        $token = $request->header('X-Internal-Token') ?? $request->bearerToken();

        if (!$token || !hash_equals($expected, $token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
