<?php

namespace App\Http\Controllers;

use App\Integrations\WhatsApp\ZApiClient;
use App\Services\ConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private ZApiClient $zapi,
        private ConversionService $conversionService
    ) {}

    /**
     * Webhook do WhatsApp (Z-API).
     */
    public function whatsapp(Request $request): JsonResponse
    {
        // Valida assinatura
        $signature = $request->header('X-Signature');
        if (!$this->zapi->isSignatureValid($request->getContent(), $signature)) {
            Log::warning('WhatsApp webhook: Invalid signature');
            return response()->json(['status' => 'invalid_signature'], 401);
        }

        try {
            $payload = $this->zapi->normalizeInbound($request->all());

            Log::info('WhatsApp webhook received', [
                'event' => $payload['event'],
                'phone' => $payload['phone'],
            ]);

            // Processa evento de mensagem recebida
            if ($payload['event'] === 'message') {
                // Registra como conversao de contato
                $this->conversionService->registerContactConversion([
                    'phone' => $payload['phone'],
                    'name' => $payload['name'],
                ], []);
            }

            return response()->json(['status' => 'ok']);

        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Webhook de conversao (site).
     */
    public function conversion(Request $request): JsonResponse
    {
        try {
            $type = $request->input('type', 'lead');

            $result = match ($type) {
                'lead' => $this->conversionService->registerLeadConversion(
                    $request->input('lead', []),
                    $request->input('source', [])
                ),
                'contact' => $this->conversionService->registerContactConversion(
                    $request->input('contact', []),
                    $request->input('source', [])
                ),
                'registration' => $this->conversionService->registerRegistrationConversion(
                    $request->input('user', []),
                    $request->input('source', [])
                ),
                default => ['error' => 'Unknown conversion type'],
            };

            return response()->json(['status' => 'ok', 'result' => $result]);

        } catch (\Throwable $e) {
            Log::error('Conversion webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Webhook do Meta (Facebook/Instagram).
     */
    public function meta(Request $request): JsonResponse
    {
        // Verificacao de challenge para configuracao do webhook
        if ($request->has('hub_mode') && $request->input('hub_mode') === 'subscribe') {
            $verifyToken = config('integrations.meta.webhook_verify_token');

            if ($request->input('hub_verify_token') === $verifyToken) {
                return response()->json((int) $request->input('hub_challenge'));
            }

            return response()->json(['error' => 'Invalid verify token'], 403);
        }

        try {
            $payload = $request->all();

            Log::info('Meta webhook received', [
                'object' => $payload['object'] ?? 'unknown',
            ]);

            // Processa eventos do Meta
            foreach (($payload['entry'] ?? []) as $entry) {
                foreach (($entry['messaging'] ?? []) as $messaging) {
                    // Processa mensagens do Messenger
                    if (isset($messaging['message'])) {
                        Log::info('Messenger message received', [
                            'sender_id' => $messaging['sender']['id'] ?? null,
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'ok']);

        } catch (\Throwable $e) {
            Log::error('Meta webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Webhook do Google Ads.
     */
    public function googleAds(Request $request): JsonResponse
    {
        try {
            Log::info('Google Ads webhook received', $request->all());

            return response()->json(['status' => 'ok']);

        } catch (\Throwable $e) {
            Log::error('Google Ads webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }
}
