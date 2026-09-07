<?php

namespace App\Integrations\Integracoes;

use Illuminate\Support\Facades\Http;

class IntegracoesClient
{
    public function dispatchEvent(string $eventKey, array $payload): array
    {
        return $this->request('events', [
            'event_type' => $eventKey,
            'source_system' => 'atendimento',
            'payload' => $payload,
        ]);
    }

    private function request(string $path, array $payload): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout((int) config('integrations.integracoes.timeout', 8))
            ->post($this->endpoint($path), $payload);

        return [
            'status' => $response->status(),
            'ok' => $response->successful(),
            'body' => $response->json(),
        ];
    }

    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('integrations.integracoes.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    private function headers(): array
    {
        $token = config('integrations.integracoes.token');

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
            $headers['X-API-Key'] = $token;
            $headers['X-Internal-Token'] = $token;
        }

        return $headers;
    }
}
