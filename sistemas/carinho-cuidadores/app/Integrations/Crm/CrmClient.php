<?php

namespace App\Integrations\Crm;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o sistema CRM interno.
 *
 * Endpoints principais:
 * - POST /caregivers - Sincroniza cuidador
 * - POST /incidents - Registra incidente
 * - POST /ratings - Sincroniza avaliacao
 * - GET /caregivers/{id}/history - Historico do cuidador
 */
class CrmClient
{
    /**
     * Sincroniza dados do cuidador com o CRM.
     */
    public function syncCaregiver(array $payload): array
    {
        return $this->request('caregivers', $payload);
    }

    /**
     * Atualiza status do cuidador no CRM.
     */
    public function updateCaregiverStatus(int $caregiverId, string $status, ?string $reason = null): array
    {
        return $this->request("caregivers/{$caregiverId}/status", [
            'status' => $status,
            'reason' => $reason,
            'changed_at' => now()->toIso8601String(),
        ], 'PATCH');
    }

    /**
     * Registra incidente no CRM.
     */
    public function registerIncident(array $payload): array
    {
        return $this->request('incidents', [
            'source' => 'cuidadores',
            'caregiver_id' => $payload['caregiver_id'] ?? null,
            'service_id' => $payload['service_id'] ?? null,
            'incident_type' => $payload['incident_type'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'occurred_at' => $payload['occurred_at'] ?? now()->toIso8601String(),
        ]);
    }

    /**
     * Sincroniza avaliacao com o CRM.
     */
    public function syncRating(array $payload): array
    {
        return $this->request('ratings', [
            'source' => 'cuidadores',
            'caregiver_id' => $payload['caregiver_id'] ?? null,
            'service_id' => $payload['service_id'] ?? null,
            'score' => $payload['score'] ?? null,
            'comment' => $payload['comment'] ?? null,
            'created_at' => $payload['created_at'] ?? now()->toIso8601String(),
        ]);
    }

    /**
     * Obtem historico do cuidador no CRM.
     */
    public function getCaregiverHistory(int $caregiverId): array
    {
        return $this->request("caregivers/{$caregiverId}/history", [], 'GET');
    }

    /**
     * Busca cuidador no CRM por telefone.
     */
    public function findByPhone(string $phone): array
    {
        $normalizedPhone = preg_replace('/\D+/', '', $phone);
        return $this->request("caregivers/search?phone={$normalizedPhone}", [], 'GET');
    }

    /**
     * Registra evento no CRM.
     */
    public function logEvent(string $eventType, array $data): array
    {
        return $this->request('events', [
            'source' => 'cuidadores',
            'event_type' => $eventType,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
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
            'X-Source' => 'carinho-cuidadores',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $headers;
    }
}
