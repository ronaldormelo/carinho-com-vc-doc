<?php

namespace App\Integrations\Cuidadores;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o sistema de Cuidadores.
 *
 * Endpoints principais:
 * - GET /caregivers/available - Busca cuidadores disponiveis
 * - GET /caregivers/{id} - Obtem dados do cuidador
 * - GET /caregivers/{id}/availability - Consulta disponibilidade
 * - POST /caregivers/{id}/assignments - Notifica alocacao
 */
class CuidadoresClient
{
    /**
     * Busca cuidadores disponiveis para alocacao.
     */
    public function findAvailable(array $filters): array
    {
        $queryParams = http_build_query([
            'service_type' => $filters['service_type'] ?? null,
            'start_date' => $filters['start_date'] ?? null,
            'end_date' => $filters['end_date'] ?? null,
            'urgency' => $filters['urgency'] ?? null,
            'skills' => isset($filters['skills']) ? implode(',', $filters['skills']) : null,
            'region' => $filters['region'] ?? null,
            'max_radius_km' => $filters['max_radius_km'] ?? null,
        ]);

        return $this->request("caregivers/available?{$queryParams}", [], 'GET');
    }

    /**
     * Obtem dados de um cuidador.
     */
    public function getCaregiver(int $caregiverId): array
    {
        return $this->request("caregivers/{$caregiverId}", [], 'GET');
    }

    /**
     * Consulta disponibilidade do cuidador.
     */
    public function getAvailability(int $caregiverId, ?string $startDate = null, ?string $endDate = null): array
    {
        $queryParams = http_build_query(array_filter([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $path = "caregivers/{$caregiverId}/availability";
        if ($queryParams) {
            $path .= "?{$queryParams}";
        }

        return $this->request($path, [], 'GET');
    }

    /**
     * Notifica alocacao ao cuidador.
     */
    public function notifyAssignment(int $caregiverId, array $data): array
    {
        return $this->request("caregivers/{$caregiverId}/assignments", [
            'source' => 'operacao',
            'service_request_id' => $data['service_request_id'] ?? null,
            'assignment_id' => $data['assignment_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'notified_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Notifica cancelamento de alocacao.
     */
    public function notifyAssignmentCancellation(int $caregiverId, int $assignmentId, string $reason): array
    {
        return $this->request("caregivers/{$caregiverId}/assignments/{$assignmentId}/cancel", [
            'reason' => $reason,
            'canceled_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Obtem habilidades do cuidador.
     */
    public function getCaregiverSkills(int $caregiverId): array
    {
        return $this->request("caregivers/{$caregiverId}/skills", [], 'GET');
    }

    /**
     * Obtem regioes atendidas pelo cuidador.
     */
    public function getCaregiverRegions(int $caregiverId): array
    {
        return $this->request("caregivers/{$caregiverId}/regions", [], 'GET');
    }

    /**
     * Obtem avaliacao media do cuidador.
     */
    public function getCaregiverRating(int $caregiverId): array
    {
        return $this->request("caregivers/{$caregiverId}/rating", [], 'GET');
    }

    /**
     * Registra evento do cuidador.
     */
    public function logEvent(int $caregiverId, string $eventType, array $data): array
    {
        return $this->request("caregivers/{$caregiverId}/events", [
            'source' => 'operacao',
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
                ->timeout((int) config('integrations.cuidadores.timeout', 8));

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
                Log::warning('Cuidadores request failed', [
                    'path' => $path,
                    'method' => $method,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Cuidadores request error', [
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
        $baseUrl = rtrim((string) config('integrations.cuidadores.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $token = config('integrations.cuidadores.token');

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
