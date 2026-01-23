<?php

namespace App\Integrations\Integracoes;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o Hub de Integracoes.
 *
 * Endpoints principais:
 * - POST /events/publish - Publica evento
 * - POST /automations/trigger - Dispara automacao
 */
class IntegracoesClient
{
    /**
     * Publica evento no hub.
     */
    public function publishEvent(string $event, array $data): array
    {
        return $this->request('events/publish', [
            'event' => $event,
            'data' => $data,
            'source' => 'carinho-documentos-lgpd',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Publica evento de contrato criado.
     */
    public function publishContractCreated(array $data): array
    {
        return $this->publishEvent('documents.contract.created', $data);
    }

    /**
     * Publica evento de contrato assinado.
     */
    public function publishContractSigned(array $data): array
    {
        return $this->publishEvent('documents.contract.signed', $data);
    }

    /**
     * Publica evento de consentimento concedido.
     */
    public function publishConsentGranted(array $data): array
    {
        return $this->publishEvent('documents.consent.granted', $data);
    }

    /**
     * Publica evento de consentimento revogado.
     */
    public function publishConsentRevoked(array $data): array
    {
        return $this->publishEvent('documents.consent.revoked', $data);
    }

    /**
     * Publica evento de solicitacao LGPD.
     */
    public function publishDataRequest(array $data): array
    {
        return $this->publishEvent('documents.lgpd.request', $data);
    }

    /**
     * Dispara automacao.
     */
    public function triggerAutomation(string $automation, array $data): array
    {
        return $this->request('automations/trigger', [
            'automation' => $automation,
            'data' => $data,
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Realiza requisicao para a API.
     */
    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        try {
            $request = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.integracoes.timeout', 8));

            $response = match ($method) {
                'GET' => $request->get($this->endpoint($path)),
                default => $request->post($this->endpoint($path), $payload),
            };

            $result = [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->json(),
            ];

            if (!$response->successful()) {
                Log::warning('Integracoes request failed', [
                    'path' => $path,
                    'method' => $method,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Integracoes request error', [
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
        $baseUrl = rtrim((string) config('integrations.integracoes.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $token = config('integrations.integracoes.token');

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Source' => 'carinho-documentos-lgpd',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $headers;
    }
}
