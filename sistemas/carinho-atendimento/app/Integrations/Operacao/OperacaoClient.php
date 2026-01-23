<?php

namespace App\Integrations\Operacao;

use Illuminate\Support\Facades\Http;

class OperacaoClient
{
    public function notifyEmergency(array $payload): array
    {
        return $this->request('emergencies', $payload);
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

        return $token ? ['Authorization' => "Bearer {$token}"] : [];
    }
}
