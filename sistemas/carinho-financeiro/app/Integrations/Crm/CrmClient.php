<?php

namespace App\Integrations\Crm;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integração com o sistema CRM (carinho-crm).
 *
 * Responsável por:
 * - Obter dados de contratos e valores acordados
 * - Obter dados de clientes
 * - Notificar eventos financeiros
 */
class CrmClient
{
    protected string $baseUrl;
    protected string $token;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('integrations.crm.base_url', ''), '/');
        $this->token = config('integrations.crm.token', '');
        $this->timeout = config('integrations.crm.timeout', 8);
    }

    /**
     * Obtém dados de um contrato.
     */
    public function getContract(int $contractId): ?array
    {
        $response = $this->request('GET', "/contracts/{$contractId}");

        if ($response['success']) {
            return $response['data'];
        }

        return null;
    }

    /**
     * Obtém dados de um cliente.
     */
    public function getClient(int $clientId): ?array
    {
        $response = $this->request('GET', "/clients/{$clientId}");

        if ($response['success']) {
            return $response['data'];
        }

        return null;
    }

    /**
     * Obtém telefone do cliente.
     */
    public function getClientPhone(int $clientId): ?string
    {
        $client = $this->getClient($clientId);
        return $client['phone'] ?? $client['lead']['phone'] ?? null;
    }

    /**
     * Obtém email do cliente.
     */
    public function getClientEmail(int $clientId): ?string
    {
        $client = $this->getClient($clientId);
        return $client['email'] ?? $client['lead']['email'] ?? null;
    }

    /**
     * Notifica CRM sobre fatura criada.
     */
    public function notifyInvoiceCreated(int $contractId, int $invoiceId, float $amount): bool
    {
        $response = $this->request('POST', '/webhooks/internal', [
            'event' => 'invoice.created',
            'payload' => [
                'contract_id' => $contractId,
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

        return $response['success'];
    }

    /**
     * Notifica CRM sobre pagamento confirmado.
     */
    public function notifyPaymentConfirmed(int $contractId, int $invoiceId, float $amount): bool
    {
        $response = $this->request('POST', '/webhooks/internal', [
            'event' => 'payment.confirmed',
            'payload' => [
                'contract_id' => $contractId,
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

        return $response['success'];
    }

    /**
     * Notifica CRM sobre fatura vencida.
     */
    public function notifyInvoiceOverdue(int $contractId, int $invoiceId, float $amount): bool
    {
        $response = $this->request('POST', '/webhooks/internal', [
            'event' => 'invoice.overdue',
            'payload' => [
                'contract_id' => $contractId,
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

        return $response['success'];
    }

    /**
     * Realiza requisição para a API do CRM.
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        if (empty($this->baseUrl) || empty($this->token)) {
            Log::debug('CRM client não configurado');
            return ['success' => false, 'error' => 'CRM não configurado'];
        }

        try {
            $request = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout($this->timeout);

            $url = $this->baseUrl . $endpoint;

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $data),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'DELETE' => $request->delete($url),
                default => throw new \Exception("Método não suportado: {$method}"),
            };

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data') ?? $response->json(),
                ];
            }

            Log::warning('CRM request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Request failed',
            ];

        } catch (\Exception $e) {
            Log::error('CRM request error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
