<?php

namespace App\Integrations\Operacao;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o sistema de Operacao.
 *
 * Endpoints principais:
 * - GET /caregivers/{id}/availability - Consulta disponibilidade
 * - POST /caregivers/{id}/allocations - Registra alocacao
 * - POST /services/{id}/checkin - Check-in do cuidador
 * - POST /services/{id}/checkout - Check-out do cuidador
 */
class OperacaoClient
{
    /**
     * Sincroniza disponibilidade do cuidador.
     */
    public function syncAvailability(int $caregiverId, array $availability): array
    {
        return $this->request("caregivers/{$caregiverId}/availability", [
            'availability' => $availability,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Consulta disponibilidade do cuidador.
     */
    public function getAvailability(int $caregiverId): array
    {
        return $this->request("caregivers/{$caregiverId}/availability", [], 'GET');
    }

    /**
     * Notifica que cuidador foi ativado.
     */
    public function notifyCaregiverActivated(int $caregiverId, array $data): array
    {
        return $this->request("caregivers/{$caregiverId}/activated", [
            'caregiver_id' => $caregiverId,
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
            'skills' => $data['skills'] ?? [],
            'regions' => $data['regions'] ?? [],
            'activated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Notifica que cuidador foi desativado.
     */
    public function notifyCaregiverDeactivated(int $caregiverId, ?string $reason = null): array
    {
        return $this->request("caregivers/{$caregiverId}/deactivated", [
            'reason' => $reason,
            'deactivated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Registra check-in do cuidador em um servico.
     */
    public function checkin(int $serviceId, int $caregiverId, array $data = []): array
    {
        return $this->request("services/{$serviceId}/checkin", [
            'caregiver_id' => $caregiverId,
            'timestamp' => now()->toIso8601String(),
            'location' => $data['location'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Registra check-out do cuidador em um servico.
     */
    public function checkout(int $serviceId, int $caregiverId, array $data = []): array
    {
        return $this->request("services/{$serviceId}/checkout", [
            'caregiver_id' => $caregiverId,
            'timestamp' => now()->toIso8601String(),
            'location' => $data['location'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Consulta alocacoes do cuidador.
     */
    public function getAllocations(int $caregiverId, ?string $status = null): array
    {
        $path = "caregivers/{$caregiverId}/allocations";
        if ($status) {
            $path .= "?status={$status}";
        }

        return $this->request($path, [], 'GET');
    }

    /**
     * Busca cuidadores disponiveis para alocacao.
     */
    public function findAvailableCaregivers(array $filters): array
    {
        return $this->request('caregivers/available', $filters);
    }

    /**
     * Registra incidente operacional.
     */
    public function registerIncident(int $serviceId, array $data): array
    {
        return $this->request("services/{$serviceId}/incidents", [
            'caregiver_id' => $data['caregiver_id'] ?? null,
            'incident_type' => $data['incident_type'] ?? null,
            'notes' => $data['notes'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? now()->toIso8601String(),
        ]);
    }

    /**
     * Realiza requisicao para a API.
     */
    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        try {
            $request = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.operacao.timeout', 8));

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
                Log::warning('Operacao request failed', [
                    'path' => $path,
                    'method' => $method,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Operacao request error', [
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
        $baseUrl = rtrim((string) config('integrations.operacao.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $token = config('integrations.operacao.token');

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
