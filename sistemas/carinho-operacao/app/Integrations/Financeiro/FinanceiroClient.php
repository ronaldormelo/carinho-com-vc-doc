<?php

namespace App\Integrations\Financeiro;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o sistema Financeiro.
 *
 * Endpoints principais:
 * - POST /services - Registra servico para cobranca
 * - POST /services/{id}/complete - Finaliza servico
 * - POST /cancellations - Registra cancelamento
 * - POST /repasses - Solicita repasse para cuidador
 */
class FinanceiroClient
{
    /**
     * Registra servico para cobranca.
     */
    public function registerService(array $data): array
    {
        return $this->request('services', [
            'source' => 'operacao',
            'service_request_id' => $data['service_request_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'caregiver_id' => $data['caregiver_id'] ?? null,
            'service_type' => $data['service_type'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'hours' => $data['hours'] ?? null,
            'registered_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Finaliza servico para cobranca.
     */
    public function completeService(int $serviceRequestId, array $data): array
    {
        return $this->request("services/{$serviceRequestId}/complete", [
            'actual_hours' => $data['actual_hours'] ?? null,
            'completed_at' => $data['completed_at'] ?? now()->toIso8601String(),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Registra cancelamento.
     */
    public function registerCancellation(array $data): array
    {
        return $this->request('cancellations', [
            'source' => 'operacao',
            'service_request_id' => $data['service_request_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'reason' => $data['reason'] ?? null,
            'fee_type' => $data['fee_type'] ?? null, // free, reduced, full
            'fee_percent' => $data['fee_percent'] ?? null,
            'canceled_at' => $data['canceled_at'] ?? now()->toIso8601String(),
        ]);
    }

    /**
     * Solicita repasse para cuidador.
     */
    public function requestRepasse(array $data): array
    {
        return $this->request('repasses', [
            'source' => 'operacao',
            'caregiver_id' => $data['caregiver_id'] ?? null,
            'service_request_id' => $data['service_request_id'] ?? null,
            'schedule_ids' => $data['schedule_ids'] ?? [],
            'total_hours' => $data['total_hours'] ?? null,
            'requested_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Consulta situacao financeira do servico.
     */
    public function getServiceFinancialStatus(int $serviceRequestId): array
    {
        return $this->request("services/{$serviceRequestId}/status", [], 'GET');
    }

    /**
     * Registra horas trabalhadas.
     */
    public function registerWorkedHours(array $data): array
    {
        return $this->request('hours', [
            'source' => 'operacao',
            'schedule_id' => $data['schedule_id'] ?? null,
            'caregiver_id' => $data['caregiver_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'shift_date' => $data['shift_date'] ?? null,
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'total_hours' => $data['total_hours'] ?? null,
        ]);
    }

    /**
     * Notifica evento financeiro relevante.
     */
    public function notifyEvent(string $eventType, array $data): array
    {
        return $this->request('events', [
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
                ->timeout((int) config('integrations.financeiro.timeout', 10));

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
                Log::warning('Financeiro request failed', [
                    'path' => $path,
                    'method' => $method,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Financeiro request error', [
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
        $baseUrl = rtrim((string) config('integrations.financeiro.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $token = config('integrations.financeiro.token');

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
