<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para rastrear parametros UTM.
 */
class TrackUtm
{
    /**
     * Parametros UTM a serem rastreados.
     */
    private const UTM_PARAMS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Armazena parametros UTM na sessao se presentes na URL
        foreach (self::UTM_PARAMS as $param) {
            if ($request->has($param)) {
                $request->session()->put($param, $request->input($param));
            }
        }

        return $next($request);
    }
}
