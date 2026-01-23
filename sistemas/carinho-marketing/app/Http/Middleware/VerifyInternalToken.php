<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyInternalToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = config('integrations.internal.token');

        if (!$expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Internal token not configured',
            ], 500);
        }

        $providedToken = $request->header('X-Internal-Token')
            ?? $request->header('Authorization');

        // Remove 'Bearer ' prefix if present
        if ($providedToken && str_starts_with($providedToken, 'Bearer ')) {
            $providedToken = substr($providedToken, 7);
        }

        if (!$providedToken || !hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        return $next($request);
    }
}
