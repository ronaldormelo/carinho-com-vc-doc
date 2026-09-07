<?php

namespace App\Integrations\Crm;

use Illuminate\Support\Facades\Http;

class CrmClient
{
    public function upsertLead(array $payload): array
    {
        return $this->request('v1/public/leads', [
            'name' => $payload['name'] ?? $payload['contact']['name'] ?? 'Contato WhatsApp',
            'phone' => $payload['phone'] ?? $payload['contact']['phone'] ?? '',
            'email' => $payload['email'] ?? $payload['contact']['email'] ?? null,
            'city' => $payload['city'] ?? $payload['contact']['city'] ?? 'nao_informada',
            'source' => $payload['source'] ?? 'atendimento',
        ]);
    }

    public function registerIncident(array $payload): array
    {
        $leadId = $payload['lead_id'] ?? null;

        if (!$leadId) {
            return [
                'status' => 501,
                'ok' => false,
                'body' => null,
                'error' => 'CRM não possui resource de incidents',
            ];
        }

        return $this->requestHost('webhooks/internal/atendimento/interaction', [
            'lead_id' => $leadId,
            'channel_id' => 1,
            'summary' => '[Atendimento] Incidente: ' . ($payload['notes'] ?? $payload['severity'] ?? ''),
        ]);
    }

    private function request(string $path, array $payload): array
    {
        return $this->send($this->endpoint($path), $payload);
    }

    private function requestHost(string $path, array $payload): array
    {
        $baseUrl = rtrim((string) config('integrations.crm.base_url'), '/');
        $host = preg_replace('#/api$#', '', $baseUrl);

        return $this->send("{$host}/{$path}", $payload);
    }

    private function send(string $url, array $payload): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout((int) config('integrations.crm.timeout', 8))
            ->post($url, $payload);

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

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Service-Origin' => 'atendimento',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
            $headers['X-Internal-Token'] = $token;
            $headers['X-API-Key'] = $token;
        }

        return $headers;
    }
}
