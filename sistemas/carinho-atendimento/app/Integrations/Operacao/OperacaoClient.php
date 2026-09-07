<?php

namespace App\Integrations\Operacao;

use Illuminate\Support\Facades\Http;

class OperacaoClient
{
    public function notifyEmergency(array $payload): array
    {
        $serviceRequestId = $payload['service_request_id'] ?? null;

        if (!$serviceRequestId) {
            return [
                'status' => 501,
                'ok' => false,
                'body' => null,
                'error' => 'Operação exige service_request_id para emergência',
            ];
        }

        $severity = $payload['severity'] ?? $payload['severity_id'] ?? 'medium';
        $severityId = match ((string) $severity) {
            '1', 'low' => 1,
            '2', 'medium' => 2,
            '3', 'high' => 3,
            '4', 'critical' => 4,
            default => is_numeric($severity) ? (int) $severity : 2,
        };

        return $this->request('emergencies', [
            'service_request_id' => (int) $serviceRequestId,
            'severity_id' => $severityId,
            'description' => $payload['description'] ?? $payload['notes'] ?? 'Emergência do atendimento',
        ]);
    }

    private function request(string $path, array $payload): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout((int) config('integrations.operacao.timeout', 8))
            ->post($this->endpoint($path), $payload);

        return [
            'status' => $response->status(),
            'ok' => $response->successful(),
            'body' => $response->json(),
        ];
    }

    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('integrations.operacao.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    private function headers(): array
    {
        $token = config('integrations.operacao.token');

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
            $headers['X-Internal-Token'] = $token;
        }

        return $headers;
    }
}
