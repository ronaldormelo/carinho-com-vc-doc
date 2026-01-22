<?php

namespace App\Integrations\Operacao;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integração com o sistema de Operação (carinho-operacao).
 *
 * Responsável por:
 * - Obter dados de serviços executados
 * - Obter horas trabalhadas por cuidador
 * - Sincronizar dados para faturamento
 */
class OperacaoClient
{
    protected string $baseUrl;
    protected string $token;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('integrations.operacao.base_url', ''), '/');
        $this->token = config('integrations.operacao.token', '');
        $this->timeout = config('integrations.operacao.timeout', 8);
    }

    /**
     * Obtém serviços de um cliente em um período.
     */
    public function getClientServices(int $clientId, string $startDate, string $endDate): array
    {
        $response = $this->request('GET', '/services', [
            'client_id' => $clientId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'completed',
        ]);

        if ($response['success']) {
            return $response['data'] ?? [];
        }

        return [];
    }

    /**
     * Obtém serviços de um cuidador em um período.
     */
    public function getCaregiverServices(int $caregiverId, string $startDate, string $endDate): array
    {
        $response = $this->request('GET', '/services', [
            'caregiver_id' => $caregiverId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'completed',
        ]);

        if ($response['success']) {
            return $response['data'] ?? [];
        }

        return [];
    }

    /**
     * Obtém detalhes de um serviço específico.
     */
    public function getService(int $serviceId): ?array
    {
        $response = $this->request('GET', "/services/{$serviceId}");

        if ($response['success']) {
            return $response['data'];
        }

        return null;
    }

    /**
     * Obtém total de horas de um cuidador em um período.
     */
    public function getCaregiverHours(int $caregiverId, string $startDate, string $endDate): float
    {
        $services = $this->getCaregiverServices($caregiverId, $startDate, $endDate);
        
        return array_reduce($services, function ($total, $service) {
            return $total + ($service['hours'] ?? 0);
        }, 0);
    }

    /**
     * Marca serviços como faturados.
     */
    public function markServicesAsInvoiced(array $serviceIds, int $invoiceId): bool
    {
        $response = $this->request('POST', '/services/mark-invoiced', [
            'service_ids' => $serviceIds,
            'invoice_id' => $invoiceId,
        ]);

        return $response['success'];
    }

    /**
     * Obtém serviços pendentes de faturamento.
     */
    public function getPendingInvoicingServices(int $clientId): array
    {
        $response = $this->request('GET', '/services/pending-invoicing', [
            'client_id' => $clientId,
        ]);

        if ($response['success']) {
            return $response['data'] ?? [];
        }

        return [];
    }

    /**
     * Realiza requisição para a API de Operação.
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        if (empty($this->baseUrl) || empty($this->token)) {
            Log::debug('Operacao client não configurado');
            return ['success' => false, 'error' => 'Operação não configurada'];
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
                default => throw new \Exception("Método não suportado: {$method}"),
            };

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data') ?? $response->json(),
                ];
            }

            Log::warning('Operacao request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Request failed',
            ];

        } catch (\Exception $e) {
            Log::error('Operacao request error', [
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
