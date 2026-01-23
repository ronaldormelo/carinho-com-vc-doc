<?php

namespace App\Integrations\Atendimento;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o sistema de Atendimento.
 *
 * Endpoints principais:
 * - POST /webhooks/documents/terms-sent
 * - POST /webhooks/documents/privacy-sent
 * - POST /notifications/send
 */
class AtendimentoClient
{
    /**
     * Notifica envio de termos.
     */
    public function notifyTermsSent(array $data): array
    {
        return $this->request('webhooks/documents/terms-sent', [
            'conversation_id' => $data['conversation_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'terms_url' => $data['terms_url'] ?? null,
            'sent_at' => $data['sent_at'] ?? now()->toIso8601String(),
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Notifica envio de politica de privacidade.
     */
    public function notifyPrivacySent(array $data): array
    {
        return $this->request('webhooks/documents/privacy-sent', [
            'conversation_id' => $data['conversation_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'privacy_url' => $data['privacy_url'] ?? null,
            'sent_at' => $data['sent_at'] ?? now()->toIso8601String(),
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Solicita envio de notificacao via atendimento.
     */
    public function sendNotification(array $data): array
    {
        return $this->request('notifications/send', [
            'channel' => $data['channel'] ?? 'whatsapp',
            'recipient' => $data['recipient'] ?? null,
            'message' => $data['message'] ?? null,
            'template' => $data['template'] ?? null,
            'variables' => $data['variables'] ?? [],
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Solicita envio de documento via atendimento.
     */
    public function sendDocument(array $data): array
    {
        return $this->request('notifications/send-document', [
            'channel' => $data['channel'] ?? 'whatsapp',
            'recipient' => $data['recipient'] ?? null,
            'document_url' => $data['document_url'] ?? null,
            'filename' => $data['filename'] ?? null,
            'message' => $data['message'] ?? null,
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Obtem URL para termos de uso.
     */
    public function getTermsUrl(): string
    {
        $domain = config('branding.domain', 'carinho.com.vc');

        return "https://{$domain}/termos";
    }

    /**
     * Obtem URL para politica de privacidade.
     */
    public function getPrivacyUrl(): string
    {
        $domain = config('branding.domain', 'carinho.com.vc');

        return "https://{$domain}/privacidade";
    }

    /**
     * Realiza requisicao para a API.
     */
    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        try {
            $request = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.atendimento.timeout', 8));

            $response = match ($method) {
                'GET' => $request->get($this->endpoint($path)),
                default => $request->post($this->endpoint($path), $payload),
            };

            $result = [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->json(),
            ];

            if (!$response->successful()) {
                Log::warning('Atendimento request failed', [
                    'path' => $path,
                    'method' => $method,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Atendimento request error', [
                'path' => $path,
                'method' => $method,
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
        $baseUrl = rtrim((string) config('integrations.atendimento.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $token = config('integrations.atendimento.token');

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Source' => 'carinho-documentos-lgpd',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $headers;
    }
}
