<?php

namespace App\Integrations\Crm;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o sistema CRM interno.
 *
 * Endpoints principais:
 * - POST /caregivers - Sincroniza cuidador
 * - POST /incidents - Registra incidente
 * - POST /ratings - Sincroniza avaliacao
 * - GET /caregivers/{id}/history - Historico do cuidador
 */
class CrmClient
{
    /**
     * Sincroniza dados do cuidador com o CRM.
     */
    public function syncCaregiver(array $payload): array
    {
        return $this->notOnCrm('POST /caregivers');
    }

    public function updateCaregiverStatus(int $caregiverId, string $status, ?string $reason = null): array
    {
        return $this->notOnCrm("PATCH /caregivers/{$caregiverId}/status");
    }

    public function registerIncident(array $payload): array
    {
        return $this->notOnCrm('POST /incidents');
    }

    public function syncRating(array $payload): array
    {
        return $this->notOnCrm('POST /ratings');
    }

    public function getCaregiverHistory(int $caregiverId): array
    {
        return $this->notOnCrm("GET /caregivers/{$caregiverId}/history");
    }

    public function findByPhone(string $phone): array
    {
        return $this->notOnCrm('GET /caregivers/search');
    }

    public function logEvent(string $eventType, array $data): array
    {
        return $this->notOnCrm('POST /events');
    }

    private function notOnCrm(string $path): array
    {
        Log::info('CRM não gerencia cuidadores neste path', ['path' => $path]);

        return [
            'status' => 501,
            'ok' => false,
            'body' => null,
            'error' => "CRM não expõe {$path}",
        ];
    }

    /**
     * Realiza requisicao para a API.
     */
    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        try {
            $request = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.crm.timeout', 8));

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
                Log::warning('CRM request failed', [
                    'path' => $path,
                    'method' => $method,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('CRM request error', [
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
        $baseUrl = rtrim((string) config('integrations.crm.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $token = config('integrations.crm.token');

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Source' => 'carinho-cuidadores',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $headers;
    }
}
