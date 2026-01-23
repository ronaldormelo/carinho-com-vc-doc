<?php

namespace App\Integrations\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cliente para integração com Z-API (WhatsApp).
 *
 * Documentação: https://developer.z-api.io/
 *
 * Endpoints principais:
 * - POST /instances/{instance}/token/{token}/send-text
 * - POST /instances/{instance}/token/{token}/send-image
 * - POST /instances/{instance}/token/{token}/send-document
 * - POST /instances/{instance}/token/{token}/send-button-list
 *
 * Usado pelo sistema financeiro para:
 * - Notificar clientes sobre faturas
 * - Enviar lembretes de vencimento
 * - Notificar cuidadores sobre repasses
 * - Enviar links de pagamento (PIX/Boleto)
 */
class ZApiClient
{
    protected string $baseUrl;
    protected string $instanceId;
    protected string $token;
    protected ?string $clientToken;
    protected int $timeout;
    protected int $connectTimeout;

    public function __construct()
    {
        $this->baseUrl = config('integrations.whatsapp.base_url', 'https://api.z-api.io');
        $this->instanceId = config('integrations.whatsapp.instance_id', '');
        $this->token = config('integrations.whatsapp.token', '');
        $this->clientToken = config('integrations.whatsapp.client_token');
        $this->timeout = config('integrations.whatsapp.timeout', 10);
        $this->connectTimeout = config('integrations.whatsapp.connect_timeout', 3);
    }

    /**
     * Envia mensagem de texto.
     */
    public function sendTextMessage(string $phone, string $message): array
    {
        return $this->request('send-text', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
        ]);
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
     * Envia mensagem com botões de ação.
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
     * Envia mensagem de template (para mensagens estruturadas).
     */
    public function sendTemplateMessage(string $phone, string $templateName, array $params = []): array
    {
        return $this->request('send-template', [
            'phone' => $this->normalizePhone($phone),
            'template' => $templateName,
            'params' => $params,
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
            'phone' => $this->normalizePhone((string) $rawPhone),
            'name' => (string) (data_get($payload, 'senderName') ?? data_get($payload, 'sender.name') ?? ''),
            'body' => is_string($body) ? $body : json_encode($body),
            'media_url' => $mediaUrl,
            'button_response' => $buttonResponse,
            'received_at' => data_get($payload, 'timestamp')
                ? \Carbon\Carbon::createFromTimestamp((int) data_get($payload, 'timestamp'))
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
            return true;
        }

        if (!$signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Verifica status da instância.
     */
    public function getInstanceStatus(): array
    {
        return $this->request('status', [], 'GET');
    }

    /**
     * Obtém QR Code para conexão.
     */
    public function getQrCode(): array
    {
        return $this->request('qr-code', [], 'GET');
    }

    /**
     * Desconecta instância.
     */
    public function disconnect(): array
    {
        return $this->request('disconnect', [], 'GET');
    }

    /**
     * Verifica se instância está conectada.
     */
    public function isConnected(): bool
    {
        $status = $this->getInstanceStatus();
        return ($status['body']['connected'] ?? false) === true;
    }

    /**
     * Realiza requisição para a API.
     */
    protected function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        if (empty($this->instanceId) || empty($this->token)) {
            Log::warning('Z-API não configurada');
            return [
                'status' => 0,
                'ok' => false,
                'body' => null,
                'error' => 'Z-API não configurada',
            ];
        }

        try {
            $request = Http::withHeaders($this->headers())
                ->connectTimeout($this->connectTimeout)
                ->timeout($this->timeout);

            $url = $this->endpoint($path);

            if ($method === 'GET') {
                $response = $request->get($url);
            } else {
                $response = $request->post($url, $payload);
            }

            $result = [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->json(),
            ];

            if (!$response->successful()) {
                Log::warning('Z-API request failed', [
                    'path' => $path,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }

            return $result;

        } catch (\Exception $e) {
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
    protected function endpoint(string $path): string
    {
        $baseUrl = rtrim($this->baseUrl, '/');
        $instanceId = trim($this->instanceId, '/');
        $token = trim($this->token, '/');

        return "{$baseUrl}/instances/{$instanceId}/token/{$token}/{$path}";
    }

    /**
     * Retorna headers da requisição.
     */
    protected function headers(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($this->clientToken) {
            $headers['client-token'] = $this->clientToken;
        }

        return $headers;
    }

    /**
     * Normaliza número de telefone.
     */
    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '');

        // Adiciona código do país se necessário
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = '55' . substr($digits, 1);
        } elseif (strlen($digits) === 10 || strlen($digits) === 11) {
            if (!str_starts_with($digits, '55')) {
                $digits = '55' . $digits;
            }
        }

        return $digits ?: Str::of($phone)->trim()->toString();
    }
}
