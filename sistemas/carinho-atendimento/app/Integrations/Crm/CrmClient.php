<?php

namespace App\Integrations\Crm;

use Illuminate\Support\Facades\Http;

class CrmClient
{
    public function upsertLead(array $payload): array
    {
        return $this->request('leads', $payload);
    }

    public function registerIncident(array $payload): array
    {
        return $this->request('incidents', $payload);
    }

    private function request(string $path, array $payload): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout((int) config('integrations.crm.timeout', 8))
            ->post($this->endpoint($path), $payload);

        return [
            'status' => $response->status(),
            'ok' => $response->successful(),
            'body' => $response->json(),
        ];
    }

    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('integrations.crm.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    private function headers(): array
    {
        $token = config('integrations.crm.token');

        return $token ? ['Authorization' => "Bearer {$token}"] : [];
    }
}
