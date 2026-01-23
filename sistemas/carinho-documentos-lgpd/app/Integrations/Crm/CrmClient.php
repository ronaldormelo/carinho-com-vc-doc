<?php

namespace App\Integrations\Crm;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o sistema CRM.
 *
 * Endpoints principais:
 * - POST /webhooks/documents/contract-created
 * - POST /webhooks/documents/contract-signed
 * - POST /webhooks/documents/consent-updated
 * - POST /webhooks/documents/data-request
 */
class CrmClient
{
    /**
     * Notifica criacao de contrato.
     */
    public function notifyContractCreated(array $data): array
    {
        return $this->request('webhooks/documents/contract-created', [
            'document_id' => $data['document_id'] ?? null,
            'owner_type' => $data['owner_type'] ?? null,
            'owner_id' => $data['owner_id'] ?? null,
            'contract_type' => $data['contract_type'] ?? null,
            'signature_url' => $data['signature_url'] ?? null,
            'created_at' => $data['created_at'] ?? now()->toIso8601String(),
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Notifica assinatura de contrato.
     */
    public function notifyContractSigned(array $data): array
    {
        return $this->request('webhooks/documents/contract-signed', [
            'document_id' => $data['document_id'] ?? null,
            'signature_id' => $data['signature_id'] ?? null,
            'signer_type' => $data['signer_type'] ?? null,
            'signer_id' => $data['signer_id'] ?? null,
            'signed_at' => $data['signed_at'] ?? now()->toIso8601String(),
            'method' => $data['method'] ?? null,
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Notifica concessao de consentimento.
     */
    public function notifyConsentGranted(array $data): array
    {
        return $this->request('webhooks/documents/consent-updated', [
            'consent_id' => $data['consent_id'] ?? null,
            'subject_type' => $data['subject_type'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'consent_type' => $data['consent_type'] ?? null,
            'action' => 'granted',
            'granted_at' => $data['granted_at'] ?? now()->toIso8601String(),
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Notifica revogacao de consentimento.
     */
    public function notifyConsentRevoked(array $data): array
    {
        return $this->request('webhooks/documents/consent-updated', [
            'consent_id' => $data['consent_id'] ?? null,
            'subject_type' => $data['subject_type'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'consent_type' => $data['consent_type'] ?? null,
            'action' => 'revoked',
            'revoked_at' => $data['revoked_at'] ?? now()->toIso8601String(),
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Notifica solicitacao de dados LGPD.
     */
    public function notifyDataRequest(array $data): array
    {
        return $this->request('webhooks/documents/data-request', [
            'request_id' => $data['request_id'] ?? null,
            'subject_type' => $data['subject_type'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'request_type' => $data['request_type'] ?? null,
            'status' => $data['status'] ?? 'open',
            'requested_at' => $data['requested_at'] ?? now()->toIso8601String(),
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Obtem dados de cliente do CRM.
     */
    public function getClient(int $clientId): array
    {
        return $this->request("clients/{$clientId}", [], 'GET');
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
            'X-Source' => 'carinho-documentos-lgpd',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $headers;
    }
}
