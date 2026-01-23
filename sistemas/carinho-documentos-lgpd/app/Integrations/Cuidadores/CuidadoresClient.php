<?php

namespace App\Integrations\Cuidadores;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o sistema de Cuidadores.
 *
 * Endpoints principais:
 * - POST /webhooks/documents/contract-created
 * - POST /webhooks/documents/contract-signed
 * - POST /webhooks/documents/document-uploaded
 * - GET /caregivers/{id}
 */
class CuidadoresClient
{
    /**
     * Notifica criacao de contrato.
     */
    public function notifyContractCreated(array $data): array
    {
        return $this->request('webhooks/documents/contract-created', [
            'document_id' => $data['document_id'] ?? null,
            'caregiver_id' => $data['caregiver_id'] ?? null,
            'contract_type' => $data['contract_type'] ?? null,
            'signature_url' => $data['signature_url'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
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
            'caregiver_id' => $data['caregiver_id'] ?? null,
            'signature_id' => $data['signature_id'] ?? null,
            'signed_at' => $data['signed_at'] ?? now()->toIso8601String(),
            'download_url' => $data['download_url'] ?? null,
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Notifica upload de documento.
     */
    public function notifyDocumentUploaded(array $data): array
    {
        return $this->request('webhooks/documents/document-uploaded', [
            'document_id' => $data['document_id'] ?? null,
            'caregiver_id' => $data['caregiver_id'] ?? null,
            'doc_type' => $data['doc_type'] ?? null,
            'file_url' => $data['file_url'] ?? null,
            'uploaded_at' => $data['uploaded_at'] ?? now()->toIso8601String(),
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Obtem dados de cuidador.
     */
    public function getCaregiver(int $caregiverId): array
    {
        return $this->request("caregivers/{$caregiverId}", [], 'GET');
    }

    /**
     * Atualiza status de documento do cuidador.
     */
    public function updateDocumentStatus(int $caregiverId, string $docType, string $status): array
    {
        return $this->request("caregivers/{$caregiverId}/documents/status", [
            'doc_type' => $docType,
            'status' => $status,
            'updated_at' => now()->toIso8601String(),
            'source' => 'carinho-documentos-lgpd',
        ], 'PATCH');
    }

    /**
     * Realiza requisicao para a API.
     */
    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        try {
            $request = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.cuidadores.timeout', 8));

            $response = match ($method) {
                'GET' => $request->get($this->endpoint($path)),
                'PATCH' => $request->patch($this->endpoint($path), $payload),
                default => $request->post($this->endpoint($path), $payload),
            };

            $result = [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->json(),
            ];

            if (!$response->successful()) {
                Log::warning('Cuidadores request failed', [
                    'path' => $path,
                    'method' => $method,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Cuidadores request error', [
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
        $baseUrl = rtrim((string) config('integrations.cuidadores.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $token = config('integrations.cuidadores.token');

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
