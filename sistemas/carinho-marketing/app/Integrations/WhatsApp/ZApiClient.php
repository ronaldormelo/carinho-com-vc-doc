<?php

namespace App\Integrations\WhatsApp;

use App\Integrations\BaseClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cliente para integracao com Z-API (WhatsApp).
 *
 * Documentacao: https://developer.z-api.io/
 *
 * Endpoints principais:
 * - POST /instances/{instance}/token/{token}/send-text
 * - POST /instances/{instance}/token/{token}/send-image
 * - POST /instances/{instance}/token/{token}/send-document
 * - POST /instances/{instance}/token/{token}/send-button-list
 * - POST /instances/{instance}/token/{token}/send-link
 */
class ZApiClient extends BaseClient
{
    private string $instanceId;
    private string $token;
    private ?string $clientToken;

    public function __construct()
    {
        $this->baseUrl = config('integrations.whatsapp.base_url', 'https://api.z-api.io');
        $this->instanceId = config('integrations.whatsapp.instance_id', '');
        $this->token = config('integrations.whatsapp.token', '');
        $this->clientToken = config('integrations.whatsapp.client_token');
        $this->timeout = (int) config('integrations.whatsapp.timeout', 10);
        $this->connectTimeout = (int) config('integrations.whatsapp.connect_timeout', 3);
        $this->cachePrefix = 'zapi';
    }

    /**
     * Envia mensagem de texto.
     */
    public function sendTextMessage(string $phone, string $message): array
    {
        return $this->request('POST', 'send-text', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
        ]);
    }

    /**
     * Envia imagem com legenda opcional.
     */
    public function sendImage(string $phone, string $imageUrl, ?string $caption = null): array
    {
        $payload = [
            'phone' => $this->normalizePhone($phone),
            'image' => $imageUrl,
        ];

        if ($caption) {
            $payload['caption'] = $caption;
        }

        return $this->request('POST', 'send-image', $payload);
    }

    /**
     * Envia documento/arquivo.
     */
    public function sendDocument(string $phone, string $documentUrl, string $fileName): array
    {
        return $this->request('POST', 'send-document', [
            'phone' => $this->normalizePhone($phone),
            'document' => $documentUrl,
            'fileName' => $fileName,
        ]);
    }

    /**
     * Envia video.
     */
    public function sendVideo(string $phone, string $videoUrl, ?string $caption = null): array
    {
        $payload = [
            'phone' => $this->normalizePhone($phone),
            'video' => $videoUrl,
        ];

        if ($caption) {
            $payload['caption'] = $caption;
        }

        return $this->request('POST', 'send-video', $payload);
    }

    /**
     * Envia audio.
     */
    public function sendAudio(string $phone, string $audioUrl): array
    {
        return $this->request('POST', 'send-audio', [
            'phone' => $this->normalizePhone($phone),
            'audio' => $audioUrl,
        ]);
    }

    /**
     * Envia link com preview.
     */
    public function sendLink(string $phone, string $message, string $url, ?string $title = null): array
    {
        return $this->request('POST', 'send-link', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
            'linkUrl' => $url,
            'title' => $title ?? '',
        ]);
    }

    /**
     * Envia mensagem com botoes.
     */
    public function sendButtonList(string $phone, string $message, array $buttons): array
    {
        $formattedButtons = array_map(fn ($btn) => [
            'id' => $btn['id'] ?? Str::uuid()->toString(),
            'label' => $btn['label'],
        ], $buttons);

        return $this->request('POST', 'send-button-list', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
            'buttonList' => [
                'buttons' => $formattedButtons,
            ],
        ]);
    }

    /**
     * Envia mensagem com lista de opcoes.
     */
    public function sendOptionList(
        string $phone,
        string $message,
        string $buttonLabel,
        array $sections
    ): array {
        return $this->request('POST', 'send-option-list', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
            'optionList' => [
                'buttonLabel' => $buttonLabel,
                'options' => $sections,
            ],
        ]);
    }

    /**
     * Envia localizacao.
     */
    public function sendLocation(
        string $phone,
        float $latitude,
        float $longitude,
        string $name,
        string $address
    ): array {
        return $this->request('POST', 'send-location', [
            'phone' => $this->normalizePhone($phone),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'name' => $name,
            'address' => $address,
        ]);
    }

    /**
     * Envia contato.
     */
    public function sendContact(string $phone, array $contact): array
    {
        return $this->request('POST', 'send-contact', [
            'phone' => $this->normalizePhone($phone),
            'contactName' => $contact['name'],
            'contactPhone' => $this->normalizePhone($contact['phone']),
            'contactBusinessDescription' => $contact['description'] ?? '',
        ]);
    }

    /**
     * Verifica status da instancia.
     */
    public function getInstanceStatus(): array
    {
        return $this->request('GET', 'status');
    }

    /**
     * Obtem QR Code para conexao.
     */
    public function getQrCode(): array
    {
        return $this->request('GET', 'qr-code');
    }

    /**
     * Desconecta instancia.
     */
    public function disconnect(): array
    {
        return $this->request('GET', 'disconnect');
    }

    /**
     * Reinicia instancia.
     */
    public function restart(): array
    {
        return $this->request('GET', 'restart');
    }

    /**
     * Obtem informacoes do perfil conectado.
     */
    public function getProfile(): array
    {
        return $this->request('GET', 'profile');
    }

    /**
     * Verifica se numero tem WhatsApp.
     */
    public function checkNumber(string $phone): array
    {
        return $this->request('GET', 'phone-exists/' . $this->normalizePhone($phone));
    }

    /**
     * Obtem foto do perfil de um contato.
     */
    public function getProfilePicture(string $phone): array
    {
        return $this->request('GET', 'profile-picture/' . $this->normalizePhone($phone));
    }

    /**
     * Marca mensagem como lida.
     */
    public function markAsRead(string $phone, string $messageId): array
    {
        return $this->request('POST', 'read-message', [
            'phone' => $this->normalizePhone($phone),
            'messageId' => $messageId,
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
            'message_id' => data_get($payload, 'messageId') ?? data_get($payload, 'message.id'),
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
     * Realiza requisicao para a API.
     */
    protected function request(string $method, string $path, array $payload = []): array
    {
        try {
            $request = \Illuminate\Support\Facades\Http::withHeaders($this->getDefaultHeaders())
                ->connectTimeout($this->connectTimeout)
                ->timeout($this->timeout);

            $url = $this->buildZApiUrl($path);

            if ($method === 'GET') {
                $response = $request->get($url);
            } else {
                $response = $request->post($url, $payload);
            }

            $result = [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ];

            if (!$response->successful()) {
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
                'success' => false,
                'status' => 0,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Monta URL do endpoint Z-API.
     */
    private function buildZApiUrl(string $path): string
    {
        $baseUrl = rtrim($this->baseUrl, '/');

        return "{$baseUrl}/instances/{$this->instanceId}/token/{$this->token}/{$path}";
    }

    /**
     * Retorna headers padrao.
     */
    protected function getDefaultHeaders(): array
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
     * Normaliza numero de telefone.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '');

        // Remove zero inicial se tiver
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        // Adiciona codigo do pais se necessario
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            if (!str_starts_with($digits, '55')) {
                $digits = '55' . $digits;
            }
        }

        return $digits ?: Str::of($phone)->trim()->toString();
    }
}
