<?php

namespace App\Integrations\Financeiro;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o sistema Financeiro.
 *
 * Endpoints principais:
 * - POST /documents/upload - Upload de nota fiscal
 * - POST /documents/comprovante - Upload de comprovante
 * - GET /documents/{type}/{id} - Obter documento
 */
class FinanceiroClient
{
    /**
     * Notifica upload de nota fiscal.
     */
    public function notifyInvoiceUploaded(array $data): array
    {
        return $this->request('webhooks/documents/invoice-uploaded', [
            'document_id' => $data['document_id'] ?? null,
            'invoice_id' => $data['invoice_id'] ?? null,
            'file_url' => $data['file_url'] ?? null,
            'signed_url' => $data['signed_url'] ?? null,
            'uploaded_at' => $data['uploaded_at'] ?? now()->toIso8601String(),
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Notifica upload de comprovante.
     */
    public function notifyReceiptUploaded(array $data): array
    {
        return $this->request('webhooks/documents/receipt-uploaded', [
            'document_id' => $data['document_id'] ?? null,
            'payment_id' => $data['payment_id'] ?? null,
            'file_url' => $data['file_url'] ?? null,
            'signed_url' => $data['signed_url'] ?? null,
            'uploaded_at' => $data['uploaded_at'] ?? now()->toIso8601String(),
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Solicita armazenamento de nota fiscal.
     */
    public function storeInvoice(string $content, array $metadata): array
    {
        return $this->request('documents/store-invoice', [
            'content' => base64_encode($content),
            'metadata' => $metadata,
            'source' => 'carinho-documentos-lgpd',
        ]);
    }

    /**
     * Obtem URL de documento financeiro.
     */
    public function getDocumentUrl(string $type, int $id): array
    {
        return $this->request("documents/{$type}/{$id}/url", [], 'GET');
    }

    /**
     * Realiza requisicao para a API.
     */
    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        try {
            $request = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.financeiro.timeout', 8));

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
                Log::warning('Financeiro request failed', [
                    'path' => $path,
                    'method' => $method,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Financeiro request error', [
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
        $baseUrl = rtrim((string) config('integrations.financeiro.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $token = config('integrations.financeiro.token');

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
