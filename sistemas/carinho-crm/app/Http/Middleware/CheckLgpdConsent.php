<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Client;
use App\Models\Consent;

/**
 * Middleware para verificar consentimento LGPD antes de acessar dados do cliente
 */
class CheckLgpdConsent
{
    public function handle(Request $request, Closure $next, string $consentType = 'data_processing'): Response
    {
        // Obtém client_id da rota ou do request
        $clientId = $request->route('client')?->id 
            ?? $request->route('client') 
            ?? $request->input('client_id');

        if (!$clientId) {
            return $next($request);
        }

        $client = Client::find($clientId);
        if (!$client) {
            return $next($request);
        }

        // Verifica se tem consentimento válido
        $hasConsent = $client->consents()
            ->where('consent_type', $consentType)
            ->exists();

        if (!$hasConsent) {
            return response()->json([
                'error' => 'Consent required',
                'message' => "O cliente não possui consentimento para '{$consentType}'",
                'consent_type' => $consentType,
            ], 403);
        }

        return $next($request);
    }
}
