<?php

namespace App\Integrations\Integracoes;

use Illuminate\Support\Facades\Http;

class IntegracoesClient
{
    public function dispatchEvent(string $eventKey, array $payload): array
    {
        return $this->request('events', array_merge([
            'event' => $eventKey,
            'source' => 'carinho-atendimento',
        ], $payload));
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

        return $token ? ['Authorization' => "Bearer {$token}"] : [];
    }
}
