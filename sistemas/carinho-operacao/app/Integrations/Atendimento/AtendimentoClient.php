<?php

namespace App\Integrations\Atendimento;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente do Atendimento. Destino real: /inbox, não /demandas.
 */
class AtendimentoClient
{
    public function getDemanda(int $demandaId): array
    {
        return $this->request("inbox/{$demandaId}", [], 'GET');
    }

    public function getDemandasPendentes(): array
    {
        return $this->request('inbox', ['status' => 'waiting'], 'GET');
    }

    public function updateDemandaStatus(int $demandaId, string $status, ?string $notes = null): array
    {
        $result = $this->request("inbox/{$demandaId}/status", [
            'status' => $status,
        ], 'PATCH');

        if ($notes) {
            $this->request("inbox/{$demandaId}/notes", ['note' => $notes]);
        }

        return $result;
    }

    public function notifyAllocation(int $demandaId, array $data): array
    {
        $note = sprintf(
            'Alocação: serviço %s, assignment %s, cuidador %s',
            $data['service_request_id'] ?? '-',
            $data['assignment_id'] ?? '-',
            $data['caregiver_id'] ?? '-'
        );

        return $this->request("inbox/{$demandaId}/notes", ['note' => $note]);
    }

    public function notifyCompletion(int $demandaId, array $data): array
    {
        $note = sprintf(
            'Conclusão: serviço %s em %s. %s',
            $data['service_request_id'] ?? '-',
            $data['completed_at'] ?? now()->toIso8601String(),
            $data['summary'] ?? ''
        );

        return $this->request("inbox/{$demandaId}/notes", ['note' => $note]);
    }

    public function getDemandaHistory(int $demandaId): array
    {
        return $this->request("inbox/{$demandaId}/history", [], 'GET');
    }

    public function registerOccurrence(int $demandaId, array $data): array
    {
        return $this->request("inbox/{$demandaId}/incident", [
            'severity' => $data['severity'] ?? $data['type'] ?? 'medium',
            'category' => $data['type'] ?? 'operacao',
            'notes' => $data['description'] ?? null,
        ]);
    }

    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        try {
            $request = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.atendimento.timeout', 8));

            $response = match ($method) {
                'GET' => $request->get($this->endpoint($path), $payload),
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

    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('integrations.atendimento.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

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
            $headers['X-Internal-Token'] = $token;
        }

        return $headers;
    }
}
