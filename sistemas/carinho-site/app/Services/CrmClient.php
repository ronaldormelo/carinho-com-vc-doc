<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para ingestão de leads no CRM.
 *
 * Ingestão pública (sem Sanctum): POST /api/v1/public/leads
 */
class CrmClient
{
    /**
     * Cria ou atualiza lead no CRM (upsert por telefone no destino).
     */
    public function createLead(array $data): array
    {
        return $this->request('POST', '/api/v1/public/leads', $data);
    }

    /**
     * Realiza requisicao para a API do CRM.
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $baseUrl = rtrim((string) config('integrations.crm.url'), '/');
        $timeout = config('integrations.crm.timeout', 10);

        try {
            $request = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Service-Origin' => 'site',
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
