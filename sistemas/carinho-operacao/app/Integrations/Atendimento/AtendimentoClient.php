<?php

namespace App\Integrations\Atendimento;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o sistema de Atendimento.
 *
 * Endpoints principais:
 * - GET /demandas/{id} - Obtem detalhes da demanda
 * - POST /demandas/{id}/status - Atualiza status
 * - GET /demandas/pendentes - Lista demandas pendentes
 */
class AtendimentoClient
{
    /**
     * Obtem detalhes de uma demanda.
     */
    public function getDemanda(int $demandaId): array
    {
        return $this->request("demandas/{$demandaId}", [], 'GET');
    }

    /**
     * Lista demandas pendentes.
     */
    public function getDemandasPendentes(): array
    {
        return $this->request('demandas/pendentes', [], 'GET');
    }

    /**
     * Atualiza status da demanda.
     */
    public function updateDemandaStatus(int $demandaId, string $status, ?string $notes = null): array
    {
        return $this->request("demandas/{$demandaId}/status", [
            'status' => $status,
            'notes' => $notes,
            'updated_by' => 'operacao',
            'updated_at' => now()->toIso8601String(),
        ], 'PATCH');
    }

    /**
     * Notifica atendimento sobre alocacao.
     */
    public function notifyAllocation(int $demandaId, array $data): array
    {
        return $this->request("demandas/{$demandaId}/allocation", [
            'service_request_id' => $data['service_request_id'] ?? null,
            'assignment_id' => $data['assignment_id'] ?? null,
            'caregiver_id' => $data['caregiver_id'] ?? null,
            'allocated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Notifica conclusao do servico.
     */
    public function notifyCompletion(int $demandaId, array $data): array
    {
        return $this->request("demandas/{$demandaId}/completion", [
            'service_request_id' => $data['service_request_id'] ?? null,
            'completed_at' => $data['completed_at'] ?? now()->toIso8601String(),
            'summary' => $data['summary'] ?? null,
        ]);
    }

    /**
     * Obtem historico da demanda.
     */
    public function getDemandaHistory(int $demandaId): array
    {
        return $this->request("demandas/{$demandaId}/history", [], 'GET');
    }

    /**
     * Registra ocorrencia na demanda.
     */
    public function registerOccurrence(int $demandaId, array $data): array
    {
        return $this->request("demandas/{$demandaId}/occurrences", [
            'source' => 'operacao',
            'type' => $data['type'] ?? null,
            'description' => $data['description'] ?? null,
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
                ->timeout((int) config('integrations.atendimento.timeout', 8));

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
            'X-Source' => 'carinho-operacao',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $headers;
    }
}
