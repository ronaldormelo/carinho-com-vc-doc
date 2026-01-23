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
        return $this->request("clients/{$clientId}", [], 'GET');
    }

    /**
     * Sincroniza solicitacao de servico com o CRM.
     */
    public function syncServiceRequest(array $payload): array
    {
        return $this->request('service-requests', [
            'source' => 'operacao',
            'service_request_id' => $payload['service_request_id'] ?? null,
            'client_id' => $payload['client_id'] ?? null,
            'service_type' => $payload['service_type'] ?? null,
            'status' => $payload['status'] ?? null,
            'created_at' => $payload['created_at'] ?? now()->toIso8601String(),
        ]);
    }

    /**
     * Atualiza status da solicitacao no CRM.
     */
    public function updateServiceRequestStatus(int $serviceRequestId, int $statusId): array
    {
        return $this->request("service-requests/{$serviceRequestId}/status", [
            'status' => $statusId,
            'updated_at' => now()->toIso8601String(),
        ], 'PATCH');
    }

    /**
     * Obtem contatos de emergencia do cliente.
     */
    public function getEmergencyContacts(int $clientId): array
    {
        return $this->request("clients/{$clientId}/emergency-contacts", [], 'GET');
    }

    /**
     * Registra evento no CRM.
     */
    public function logEvent(string $eventType, array $data): array
    {
        return $this->request('events', [
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
        return $this->request("clients/{$clientId}/preferences", [], 'GET');
    }

    /**
     * Registra feedback do cliente.
     */
    public function registerFeedback(array $payload): array
    {
        return $this->request('feedback', [
            'source' => 'operacao',
            'client_id' => $payload['client_id'] ?? null,
            'service_request_id' => $payload['service_request_id'] ?? null,
            'caregiver_id' => $payload['caregiver_id'] ?? null,
            'rating' => $payload['rating'] ?? null,
            'comment' => $payload['comment'] ?? null,
            'created_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Realiza requisicao para a API.
     */
    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        try {
            $request = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.crm.timeout', 8));

            $response = match ($method) {
                'GET' => $request->get($this->endpoint($path)),
                'PATCH' => $request->patch($this->endpoint($path), $payload),
                'PUT' => $request->put($this->endpoint($path), $payload),
                'DELETE' => $request->delete($this->endpoint($path)),
                default => $request->post($this->endpoint($path), $payload),
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
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $headers;
    }
}
