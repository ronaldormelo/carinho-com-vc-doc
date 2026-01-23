<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * Middleware para verificacao de assinatura de webhook.
 *
 * Valida HMAC-SHA256 da requisicao.
 */
class VerifyWebhookSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $secretKey = null): Response
    {
        $signature = $request->header('X-Webhook-Signature')
            ?? $request->header('X-Signature')
            ?? $request->header('X-Hub-Signature-256');

        // Se nao ha secret configurado, permite passar (desenvolvimento)
        $secret = $secretKey
            ? config($secretKey)
            : $request->header('X-Webhook-Secret-Key');

        if (!$secret) {
            // Em producao, bloqueia se nao ha secret
            if (config('app.env') === 'production') {
                Log::warning('Webhook without secret in production', [
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                ]);

                return response()->json([
                    'error' => 'Webhook secret not configured',
                ], 500);
            }

            return $next($request);
        }

        if (!$signature) {
            Log::warning('Webhook request without signature', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Missing webhook signature',
            ], 401);
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        // Remove prefixo se existir (ex: sha256=)
        $providedSignature = preg_replace('/^sha256=/', '', $signature);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            Log::warning('Invalid webhook signature', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Invalid webhook signature',
            ], 401);
        }

        return $next($request);
    }
}
