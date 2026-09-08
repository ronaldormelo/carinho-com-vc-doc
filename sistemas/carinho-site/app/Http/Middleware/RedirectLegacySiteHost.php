<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redireciona o hostname legado site.carinho.com.vc para o apex https://carinho.com.vc.
 */
class RedirectLegacySiteHost
{
    private const LEGACY_HOST = 'site.carinho.com.vc';

    private const CANONICAL_ORIGIN = 'https://carinho.com.vc';

    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());

        if ($host === self::LEGACY_HOST) {
            return redirect()->away(self::CANONICAL_ORIGIN . $request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
