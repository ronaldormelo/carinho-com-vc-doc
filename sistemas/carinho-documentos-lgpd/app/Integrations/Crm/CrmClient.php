<?php

namespace App\Integrations\Crm;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente CRM a partir de Documentos. Webhook real: /webhooks/internal/documentos/contract-signed.
 */
class CrmClient
{
    public function notifyContractCreated(array $data): array
    {
        return $this->notImplemented('webhooks/documents/contract-created');
    }

    public function notifyContractSigned(array $data): array
    {
        return $this->requestHost('webhooks/internal/documentos/contract-signed', [
            'contract_id' => $data['contract_id'] ?? $data['document_id'] ?? null,
            'signed_at' => $data['signed_at'] ?? now()->toIso8601String(),
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? $data['method'] ?? null,
        ]);
    }

    public function notifyConsentGranted(array $data): array
    {
        return $this->notImplemented('webhooks/documents/consent-updated');
    }

    public function notifyConsentRevoked(array $data): array
    {
        return $this->notImplemented('webhooks/documents/consent-updated');
    }

    public function notifyDataRequest(array $data): array
    {
        return $this->notImplemented('webhooks/documents/data-request');
    }

    public function getClient(int $clientId): array
    {
        return $this->request("v1/clients/{$clientId}", [], 'GET');
    }

    private function notImplemented(string $path): array
    {
        Log::info('CRM não expõe rota de documentos', ['path' => $path]);

        return [
            'status' => 501,
            'ok' => false,
            'body' => null,
            'error' => "CRM não expõe {$path}",
        ];
    }

    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        return $this->send($this->endpoint($path), $payload, $method, $path);
    }

    private function requestHost(string $path, array $payload = []): array
    {
        $baseUrl = rtrim((string) config('integrations.crm.base_url'), '/');
        $host = preg_replace('#/api$#', '', $baseUrl);

        return $this->send("{$host}/{$path}", $payload, 'POST', $path);
    }

    private function send(string $url, array $payload, string $method, string $path): array
    {
        try {
            $http = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.crm.timeout', 8));

            $response = $method === 'GET'
                ? $http->get($url, $payload)
                : $http->post($url, $payload);

            $result = [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->json(),
            ];

            if (!$response->successful()) {
                Log::warning('CRM request failed', [
                    'path' => $path,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('CRM request error', [
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

    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('integrations.crm.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    private function headers(): array
    {
        $token = config('integrations.crm.token');

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Service-Origin' => 'documentos',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
            $headers['X-Internal-Token'] = $token;
            $headers['X-API-Key'] = $token;
        }

        return $headers;
    }
}
