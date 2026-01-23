<?php

namespace App\Integrations\Documentos;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o sistema de Documentos/LGPD.
 *
 * Endpoints principais:
 * - POST /documents/upload - Upload de documento
 * - POST /documents/validate - Validacao automatica
 * - POST /contracts - Criar contrato
 * - POST /contracts/{id}/sign - Registrar assinatura
 * - GET /documents/{id}/signed-url - URL assinada para visualizacao
 */
class DocumentosClient
{
    /**
     * Faz upload de documento.
     */
    public function upload(UploadedFile $file, array $metadata = []): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.documentos.upload_timeout', 60))
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($this->endpoint('documents/upload'), [
                    'metadata' => json_encode($metadata),
                    'source' => 'carinho-cuidadores',
                ]);

            return [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::error('Documentos upload error', [
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
     * Solicita validacao automatica de documento.
     */
    public function validate(array $payload): array
    {
        return $this->request('documents/validate', [
            'document_id' => $payload['document_id'] ?? null,
            'file_url' => $payload['file_url'] ?? null,
            'doc_type' => $payload['doc_type'] ?? null,
            'source' => 'carinho-cuidadores',
        ]);
    }

    /**
     * Cria novo contrato.
     */
    public function createContract(array $payload): array
    {
        return $this->request('contracts', [
            'type' => $payload['type'] ?? null,
            'template' => $payload['template'] ?? null,
            'caregiver_id' => $payload['caregiver_id'] ?? null,
            'caregiver_name' => $payload['caregiver_name'] ?? null,
            'caregiver_phone' => $payload['caregiver_phone'] ?? null,
            'caregiver_email' => $payload['caregiver_email'] ?? null,
            'variables' => $payload['variables'] ?? [],
            'source' => 'carinho-cuidadores',
        ]);
    }

    /**
     * Registra assinatura de contrato.
     */
    public function signContract(int $contractId, array $signatureData): array
    {
        return $this->request("contracts/{$contractId}/sign", [
            'signature' => $signatureData['signature'] ?? null,
            'ip_address' => $signatureData['ip_address'] ?? null,
            'user_agent' => $signatureData['user_agent'] ?? null,
            'signed_at' => $signatureData['signed_at'] ?? now()->toIso8601String(),
            'source' => 'carinho-cuidadores',
        ]);
    }

    /**
     * Obtem URL para assinatura do contrato.
     */
    public function getSignatureUrl(int $contractId): array
    {
        return $this->request("contracts/{$contractId}/signature-url", [], 'GET');
    }

    /**
     * Obtem URL assinada para visualizacao de documento.
     */
    public function getSignedUrl(string $fileUrl, int $expiresMinutes = 60): array
    {
        return $this->request('documents/signed-url', [
            'file_url' => $fileUrl,
            'expires_minutes' => $expiresMinutes,
        ]);
    }

    /**
     * Obtem status do contrato.
     */
    public function getContractStatus(int $contractId): array
    {
        return $this->request("contracts/{$contractId}/status", [], 'GET');
    }

    /**
     * Lista documentos de um cuidador.
     */
    public function listDocuments(int $caregiverId): array
    {
        return $this->request("documents?caregiver_id={$caregiverId}&source=carinho-cuidadores", [], 'GET');
    }

    /**
     * Solicita exclusao de documento (LGPD).
     */
    public function requestDeletion(int $documentId, string $reason): array
    {
        return $this->request("documents/{$documentId}/delete-request", [
            'reason' => $reason,
            'requested_at' => now()->toIso8601String(),
            'source' => 'carinho-cuidadores',
        ]);
    }

    /**
     * Solicita exportacao de dados (LGPD).
     */
    public function requestDataExport(int $caregiverId): array
    {
        return $this->request('data-export', [
            'caregiver_id' => $caregiverId,
            'source' => 'carinho-cuidadores',
            'requested_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Realiza requisicao para a API.
     */
    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        try {
            $request = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.documentos.timeout', 15));

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
                Log::warning('Documentos request failed', [
                    'path' => $path,
                    'method' => $method,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Documentos request error', [
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
        $baseUrl = rtrim((string) config('integrations.documentos.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $token = config('integrations.documentos.token');

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
