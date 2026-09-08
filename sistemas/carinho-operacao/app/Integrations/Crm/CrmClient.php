<?php

namespace App\Integrations\Crm;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o sistema CRM interno.
 *
 * Endpoints principais:
 * - GET /clients/{id} - Obtem dados do cliente
 * - POST /service-requests - Sincroniza solicitacao
 * - PATCH /service-requests/{id}/status - Atualiza status
 * - POST /events - Registra evento
 */
class CrmClient
{
    /**
     * Obtem dados do cliente.
     */
    public function getClient(int $clientId): array
    {
        return $this->request("v1/clients/{$clientId}", [], 'GET');
    }

    public function syncServiceRequest(array $payload): array
    {
        return $this->requestHost('webhooks/internal/operacao/service-started', [
            'client_id' => $payload['client_id'] ?? null,
            'service_date' => isset($payload['created_at'])
                ? substr((string) $payload['created_at'], 0, 10)
                : now()->toDateString(),
            'caregiver_name' => $payload['caregiver_name'] ?? null,
        ]);
    }

    public function updateServiceRequestStatus(int $serviceRequestId, int $statusId): array
    {
        Log::info('CRM não expõe status de service-request; evento ignorado', [
            'service_request_id' => $serviceRequestId,
            'status_id' => $statusId,
        ]);

        return [
            'status' => 501,
            'ok' => false,
            'body' => null,
            'error' => 'CRM não possui resource de service-request',
            'not_implemented' => true,
        ];
    }

    /**
     * Obtem contatos de emergencia do cliente.
     */
    public function getEmergencyContacts(int $clientId): array
    {
        return $this->request("v1/clients/{$clientId}", [], 'GET');
    }

    /**
     * Registra evento no CRM.
     */
    public function logEvent(string $eventType, array $data): array
    {
        $clientId = $data['client_id'] ?? null;

        if (!$clientId) {
            return [
                'status' => 501,
                'ok' => false,
                'body' => null,
                'error' => 'client_id obrigatório para registrar evento no CRM',
                'not_implemented' => true,
            ];
        }

        return $this->request("v1/clients/{$clientId}/events", [
            'source' => 'operacao',
            'event_type' => $eventType,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Obtem preferencias do cliente.
     */
    public function getClientPreferences(int $clientId): array
    {
        return $this->request("v1/clients/{$clientId}", [], 'GET');
    }

    /**
     * Registra feedback do cliente.
     */
    public function registerFeedback(array $payload): array
    {
        return [
            'status' => 501,
            'ok' => false,
            'body' => null,
            'error' => 'CRM não possui endpoint de feedback',
            'not_implemented' => true,
        ];
    }

    /**
     * Realiza requisicao para a API.
     */
    private function requestHost(string $path, array $payload = [], string $method = 'POST'): array
    {
        $baseUrl = rtrim((string) config('integrations.crm.base_url'), '/');
        $host = preg_replace('#/api$#', '', $baseUrl);

        return $this->send("{$host}/{$path}", $payload, $method, $path);
    }

    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        return $this->send($this->endpoint($path), $payload, $method, $path);
    }

    private function send(string $url, array $payload, string $method, string $path): array
    {
        try {
            $http = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.crm.timeout', 8));

            $response = match ($method) {
                'GET' => $http->get($url, $payload),
                'PATCH' => $http->patch($url, $payload),
                'PUT' => $http->put($url, $payload),
                'DELETE' => $http->delete($url),
                default => $http->post($url, $payload),
            };

            $result = [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->json(),
            ];

            if (!$response->successful()) {
                Log::warning('CRM request failed', [
                    'path' => $path,
                    'method' => $method,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('CRM request error', [
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
        $baseUrl = rtrim((string) config('integrations.crm.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $token = config('integrations.crm.token');

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Source' => 'carinho-operacao',
            'X-Service-Origin' => 'operacao',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
            $headers['X-Internal-Token'] = $token;
            $headers['X-API-Key'] = $token;
        }

        return $headers;
    }
}
