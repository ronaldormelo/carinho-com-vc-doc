<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Integrations\WhatsApp\ZApiClient;
use App\Jobs\ProcessWhatsAppInbound;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para webhooks do Z-API (WhatsApp).
 *
 * Recebe notificacoes de mensagens recebidas e status.
 */
class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private ZApiClient $zapi
    ) {}

    /**
     * Recebe webhook do Z-API.
     *
     * POST /webhooks/whatsapp
     */
    public function handle(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $signature = $request->header('X-Webhook-Signature');

        // Valida assinatura
        if (!$this->zapi->isSignatureValid($rawPayload, $signature)) {
            Log::warning('Invalid WhatsApp webhook signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Invalid signature',
            ], 401);
        }

        $payload = $request->all();

        Log::info('WhatsApp webhook received', [
            'event' => $payload['event'] ?? 'unknown',
            'phone' => $payload['phone'] ?? $payload['from'] ?? 'unknown',
        ]);

        // Normaliza payload
        $normalized = $this->zapi->normalizeInbound($payload);

        // Despacha para processamento assincrono
        ProcessWhatsAppInbound::dispatch($normalized);

        return response()->json([
            'status' => 'received',
        ]);
    }

    /**
     * Endpoint de verificacao (challenge).
     *
     * GET /webhooks/whatsapp
     */
    public function verify(Request $request): JsonResponse
    {
        // Alguns provedores enviam challenge para verificar webhook
        $challenge = $request->get('challenge');

        if ($challenge) {
            return response()->json([
                'challenge' => $challenge,
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'service' => 'whatsapp-webhook',
        ]);
    }

    /**
     * Recebe notificacao de status de mensagem.
     *
     * POST /webhooks/whatsapp/status
     */
    public function status(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('WhatsApp status update received', [
            'message_id' => $payload['messageId'] ?? 'unknown',
            'status' => $payload['status'] ?? 'unknown',
        ]);

        // Registra evento de status
        \App\Models\IntegrationEvent::createEvent(
            \App\Models\IntegrationEvent::TYPE_WHATSAPP_STATUS,
            \App\Models\IntegrationEvent::SOURCE_WHATSAPP,
            $payload
        );

        return response()->json([
            'status' => 'received',
        ]);
    }
}
