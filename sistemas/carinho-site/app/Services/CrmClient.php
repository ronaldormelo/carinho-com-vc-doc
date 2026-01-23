<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com sistema CRM.
 */
class CrmClient
{
    /**
     * Cria um novo lead no CRM.
     */
    public function createLead(array $data): array
    {
        $endpoint = '/api/v1/leads';

        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Atualiza um lead existente.
     */
    public function updateLead(int $id, array $data): array
    {
        $endpoint = "/api/v1/leads/{$id}";

        return $this->request('PUT', $endpoint, $data);
    }

    /**
     * Busca lead por telefone.
     */
    public function findLeadByPhone(string $phone): ?array
    {
        $endpoint = '/api/v1/leads';

        $response = $this->request('GET', $endpoint, [
            'phone' => $phone,
            'per_page' => 1,
        ]);

        if ($response['ok'] && !empty($response['data']['data'])) {
            return $response['data']['data'][0];
        }

        return null;
    }

    /**
     * Registra origem do lead (UTM).
     */
    public function registerLeadSource(int $leadId, array $utmData): array
    {
        $endpoint = "/api/v1/leads/{$leadId}/source";

        return $this->request('POST', $endpoint, $utmData);
    }

    /**
     * Realiza requisicao para a API do CRM.
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $baseUrl = rtrim((string) config('integrations.crm.url'), '/');
        $apiKey = config('integrations.crm.api_key');
        $timeout = config('integrations.crm.timeout', 10);

        if (empty($apiKey)) {
            Log::warning('CRM API key nao configurada');
            return [
                'ok' => false,
                'error' => 'API key not configured',
            ];
        }

        try {
            $request = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$apiKey}",
            ])->timeout($timeout);

            if ($method === 'GET') {
                $response = $request->get("{$baseUrl}{$endpoint}", $data);
            } else {
                $response = $request->$method("{$baseUrl}{$endpoint}", $data);
            }

            $result = [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ];

            if (!$response->successful()) {
                Log::warning('CRM request failed', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('CRM request error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
