<?php

namespace App\Integrations\WhatsApp;

use Illuminate\Support\Facades\Http;
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
 */
class ZApiClient
{
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
     * Envia mensagem de notificacao de inicio de servico.
     */
    public function sendServiceStartNotification(string $phone, array $data): array
    {
        $message = config('branding.messages.service_started') . "\n\n";
        $message .= "Cuidador(a): " . ($data['caregiver_name'] ?? 'N/A') . "\n";
        $message .= "Horario: " . ($data['start_time'] ?? 'N/A');

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Envia mensagem de notificacao de fim de servico.
     */
    public function sendServiceEndNotification(string $phone, array $data): array
    {
        $message = config('branding.messages.service_ended') . "\n\n";
        $message .= "Horario de termino: " . ($data['end_time'] ?? 'N/A');

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Envia lembrete de agendamento.
     */
    public function sendScheduleReminder(string $phone, array $data): array
    {
        $message = config('branding.messages.schedule_reminder') . "\n\n";
        $message .= "Data: " . ($data['date'] ?? 'N/A') . "\n";
        $message .= "Horario: " . ($data['start_time'] ?? 'N/A') . " - " . ($data['end_time'] ?? 'N/A') . "\n";
        $message .= "Cuidador(a): " . ($data['caregiver_name'] ?? 'N/A');

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Envia notificacao de alocacao de cuidador.
     */
    public function sendCaregiverAssignedNotification(string $phone, array $data): array
    {
        $message = config('branding.messages.caregiver_assigned') . "\n\n";
        $message .= "Cuidador(a): " . ($data['caregiver_name'] ?? 'N/A') . "\n";

        if (!empty($data['start_date'])) {
            $message .= "Inicio: " . $data['start_date'];
        }

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Envia notificacao de substituicao.
     */
    public function sendReplacementNotification(string $phone, array $data): array
    {
        $message = config('branding.messages.caregiver_replaced') . "\n\n";
        $message .= "Novo cuidador(a): " . ($data['new_caregiver_name'] ?? 'N/A');

        return $this->sendTextMessage($phone, $message);
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
     * Envia confirmacao de agendamento com botoes.
     */
    public function sendScheduleConfirmation(string $phone, array $data): array
    {
        $message = "Confirmacao de agendamento\n\n";
        $message .= "Data: " . ($data['date'] ?? 'N/A') . "\n";
        $message .= "Horario: " . ($data['start_time'] ?? 'N/A') . " - " . ($data['end_time'] ?? 'N/A') . "\n";
        $message .= "Cuidador(a): " . ($data['caregiver_name'] ?? 'N/A') . "\n\n";
        $message .= "Por favor, confirme o agendamento:";

        return $this->sendButtonList($phone, $message, [
            ['id' => 'confirm_' . ($data['schedule_id'] ?? ''), 'label' => 'Confirmar'],
            ['id' => 'reschedule_' . ($data['schedule_id'] ?? ''), 'label' => 'Reagendar'],
            ['id' => 'cancel_' . ($data['schedule_id'] ?? ''), 'label' => 'Cancelar'],
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
     * Verifica status da instancia.
     */
    public function getInstanceStatus(): array
    {
        return $this->request('status', [], 'GET');
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
        return $this->request('disconnect', [], 'GET');
    }

    /**
     * Realiza requisicao para a API.
     */
    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
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
     * Normaliza numero de telefone.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '');

        // Adiciona codigo do pais se necessario
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
