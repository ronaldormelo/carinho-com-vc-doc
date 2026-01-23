<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * Middleware para verificar autenticidade de webhooks de sistemas internos
 */
class VerifyInternalWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $serviceOrigin = $request->header('X-Service-Origin');
        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');

        // Verifica se os headers obrigatórios estão presentes
        if (!$apiKey || !$serviceOrigin) {
            Log::channel('integrations')->warning('Webhook rejeitado: headers faltando', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing required headers',
            ], 401);
        }

        // Verifica se a chave de API é válida
        $validApiKey = config('integrations.webhooks.secret');
        if ($validApiKey && $apiKey !== $validApiKey) {
            Log::channel('integrations')->warning('Webhook rejeitado: API key inválida', [
                'ip' => $request->ip(),
                'service' => $serviceOrigin,
            ]);

            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid API key',
            ], 401);
        }

        // Verifica timestamp para prevenir replay attacks (opcional)
        if ($timestamp) {
            $tolerance = config('integrations.webhooks.tolerance', 300);
            $requestTime = strtotime($timestamp);
            
            if (abs(time() - $requestTime) > $tolerance) {
                Log::channel('integrations')->warning('Webhook rejeitado: timestamp expirado', [
                    'ip' => $request->ip(),
                    'service' => $serviceOrigin,
                    'timestamp' => $timestamp,
                ]);

                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Request timestamp expired',
                ], 401);
            }
        }

        // Log do webhook recebido
        Log::channel('integrations')->info('Webhook recebido', [
            'service' => $serviceOrigin,
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}
