<?php

namespace App\Services\Integrations\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Cliente para integracao com Z-API (WhatsApp).
 *
 * Documentacao oficial: https://developer.z-api.io/
 *
 * Endpoints principais:
 * - POST /instances/{instance}/token/{token}/send-text
 * - POST /instances/{instance}/token/{token}/send-image
 * - POST /instances/{instance}/token/{token}/send-document
 * - POST /instances/{instance}/token/{token}/send-button-list
 * - POST /instances/{instance}/token/{token}/send-link
 * - GET  /instances/{instance}/token/{token}/status
 * - GET  /instances/{instance}/token/{token}/qr-code
 * - GET  /instances/{instance}/token/{token}/disconnect
 */
class ZApiClient
{
    private const CACHE_TTL = 300; // 5 minutos

    /**
     * Envia mensagem de texto simples.
     */
    public function sendTextMessage(string $phone, string $message): array
    {
        return $this->request('send-text', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
        ]);
    }

    /**
     * Envia mensagem com template de boas-vindas.
     */
    public function sendWelcomeMessage(string $phone, string $name): array
    {
        $template = config('branding.messages.welcome');
        $message = str_replace('{nome}', $name, $template);

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Envia resposta automatica para lead.
     */
    public function sendLeadAutoResponse(string $phone, string $name): array
    {
        $template = config('branding.messages.lead_response');
        $message = str_replace('{nome}', $name, $template);

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Envia solicitacao de feedback pos-servico.
     */
    public function sendFeedbackRequest(string $phone, string $clientName, string $caregiverName): array
    {
        $template = config('branding.messages.feedback_request');
        $message = str_replace(
            ['{nome}', '{cuidador}'],
            [$clientName, $caregiverName],
            $template
        );

        // Envia com botoes de avaliacao
        return $this->sendButtonList($phone, $message, [
            ['id' => 'rating_1', 'label' => '⭐ 1 - Ruim'],
            ['id' => 'rating_2', 'label' => '⭐⭐ 2 - Regular'],
            ['id' => 'rating_3', 'label' => '⭐⭐⭐ 3 - Bom'],
            ['id' => 'rating_4', 'label' => '⭐⭐⭐⭐ 4 - Muito Bom'],
            ['id' => 'rating_5', 'label' => '⭐⭐⭐⭐⭐ 5 - Excelente'],
        ]);
    }

    /**
     * Envia notificacao de servico completado.
     */
    public function sendServiceCompletedNotification(string $phone, string $name): array
    {
        $template = config('branding.messages.service_completed');
        $message = str_replace('{nome}', $name, $template);

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Envia imagem com legenda opcional.
     */
    public function sendMediaMessage(string $phone, string $mediaUrl, ?string $caption = null): array
    {
        return $this->request('send-image', [
            'phone' => $this->normalizePhone($phone),
            'image' => $mediaUrl,
            'caption' => $caption,
        ]);
    }

    /**
     * Envia documento/arquivo.
     */
    public function sendDocument(string $phone, string $documentUrl, string $fileName): array
    {
        return $this->request('send-document', [
            'phone' => $this->normalizePhone($phone),
            'document' => $documentUrl,
            'fileName' => $fileName,
        ]);
    }

    /**
     * Envia mensagem com botoes de acao.
     */
    public function sendButtonList(string $phone, string $message, array $buttons): array
    {
        $formattedButtons = array_map(fn ($btn) => [
            'id' => $btn['id'] ?? Str::uuid()->toString(),
            'label' => $btn['label'],
        ], $buttons);

        return $this->request('send-button-list', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
            'buttonList' => [
                'buttons' => $formattedButtons,
            ],
        ]);
    }

    /**
     * Envia link com preview.
     */
    public function sendLink(string $phone, string $message, string $url, ?string $title = null): array
    {
        return $this->request('send-link', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
            'linkUrl' => $url,
            'title' => $title ?? '',
        ]);
    }

    /**
     * Normaliza payload de webhook recebido.
     */
    public function normalizeInbound(array $payload): array
    {
        $rawPhone = data_get($payload, 'phone')
            ?? data_get($payload, 'from')
            ?? data_get($payload, 'sender')
            ?? data_get($payload, 'message.from');

        $body = data_get($payload, 'message')
            ?? data_get($payload, 'text')
            ?? data_get($payload, 'body')
            ?? data_get($payload, 'message.text')
            ?? '';

        $mediaUrl = data_get($payload, 'media.url')
            ?? data_get($payload, 'message.mediaUrl')
            ?? data_get($payload, 'message.media')
            ?? null;

        $buttonResponse = data_get($payload, 'buttonResponseMessage')
            ?? data_get($payload, 'message.buttonResponse')
            ?? null;

        return [
            'provider' => 'z-api',
            'event' => data_get($payload, 'event', 'message'),
            'message_id' => data_get($payload, 'messageId') ?? data_get($payload, 'message.id'),
            'phone' => $this->normalizePhone((string) $rawPhone),
            'name' => (string) (data_get($payload, 'senderName') ?? data_get($payload, 'sender.name') ?? ''),
            'body' => is_string($body) ? $body : json_encode($body),
            'media_url' => $mediaUrl,
            'button_response' => $buttonResponse,
            'is_from_me' => (bool) data_get($payload, 'isFromMe', false),
            'received_at' => data_get($payload, 'timestamp')
                ? Carbon::createFromTimestamp((int) data_get($payload, 'timestamp'))
                : now(),
            'raw' => $payload,
        ];
    }

    /**
     * Valida assinatura do webhook.
     */
    public function isSignatureValid(string $payload, ?string $signature): bool
    {
        $secret = config('integrations.whatsapp.webhook_secret');

        if (!$secret) {
            return true; // Se nao configurado, aceita (desenvolvimento)
        }

        if (!$signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Verifica status da instancia com cache.
     */
    public function getInstanceStatus(bool $fresh = false): array
    {
        $cacheKey = 'zapi_instance_status';

        if (!$fresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $status = $this->request('status', [], 'GET');

        if ($status['ok']) {
            Cache::put($cacheKey, $status, self::CACHE_TTL);
        }

        return $status;
    }

    /**
     * Verifica se instancia esta conectada.
     */
    public function isConnected(): bool
    {
        $status = $this->getInstanceStatus();

        return $status['ok'] && data_get($status, 'body.connected', false);
    }

    /**
     * Obtem QR Code para conexao.
     */
    public function getQrCode(): array
    {
        return $this->request('qr-code', [], 'GET');
    }

    /**
     * Desconecta instancia.
     */
    public function disconnect(): array
    {
        Cache::forget('zapi_instance_status');

        return $this->request('disconnect', [], 'GET');
    }

    /**
     * Verifica se numero e valido no WhatsApp.
     */
    public function checkNumber(string $phone): array
    {
        return $this->request('phone-exists', [
            'phone' => $this->normalizePhone($phone),
        ]);
    }

    /**
     * Obtem informacoes de contato.
     */
    public function getContactInfo(string $phone): array
    {
        return $this->request('contact/' . $this->normalizePhone($phone), [], 'GET');
    }

    /**
     * Obtem foto de perfil do contato.
     */
    public function getProfilePicture(string $phone): array
    {
        return $this->request('profile-picture', [
            'phone' => $this->normalizePhone($phone),
        ]);
    }

    /**
     * Marca mensagem como lida.
     */
    public function markAsRead(string $phone, string $messageId): array
    {
        return $this->request('read-message', [
            'phone' => $this->normalizePhone($phone),
            'messageId' => $messageId,
        ]);
    }

    /**
     * Realiza requisicao para a API.
     */
    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        if (!config('integrations.whatsapp.enabled')) {
            Log::info('Z-API disabled, skipping request', ['path' => $path]);

            return [
                'status' => 0,
                'ok' => false,
                'body' => null,
                'error' => 'Z-API integration is disabled',
            ];
        }

        try {
            $request = Http::withHeaders($this->headers())
                ->connectTimeout((int) config('integrations.whatsapp.connect_timeout', 3))
                ->timeout((int) config('integrations.whatsapp.timeout', 10));

            if ($method === 'GET') {
                $response = $request->get($this->endpoint($path));
            } else {
                $response = $request->post($this->endpoint($path), $payload);
            }

            $result = [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->json(),
            ];

            if ($response->successful()) {
                Log::info('Z-API request successful', [
                    'path' => $path,
                    'status' => $response->status(),
                ]);
            } else {
                Log::warning('Z-API request failed', [
                    'path' => $path,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Z-API request error', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 0,
                'ok' => false,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Monta URL do endpoint.
     */
    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('integrations.whatsapp.base_url'), '/');
        $instanceId = trim((string) config('integrations.whatsapp.instance_id'), '/');
        $token = trim((string) config('integrations.whatsapp.token'), '/');

        return "{$baseUrl}/instances/{$instanceId}/token/{$token}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $clientToken = config('integrations.whatsapp.client_token');

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($clientToken) {
            $headers['client-token'] = $clientToken;
        }

        return $headers;
    }

    /**
     * Normaliza numero de telefone para formato internacional.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '');

        // Remove zero inicial do DDD
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = '55' . substr($digits, 1);
        }
        // Adiciona codigo do Brasil se necessario
        elseif (strlen($digits) === 10 || strlen($digits) === 11) {
            if (!str_starts_with($digits, '55')) {
                $digits = '55' . $digits;
            }
        }

        return $digits ?: Str::of($phone)->trim()->toString();
    }
}
