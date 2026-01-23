<?php

namespace App\Services\Integrations;

/**
 * Integração com Carinho Financeiro (financeiro.carinho.com.vc)
 * Sincroniza contratos e dados de cobrança
 */
class CarinhoFinanceiroService extends BaseInternalService
{
    protected string $serviceName = 'carinho-financeiro';

    public function isEnabled(): bool
    {
        return config('integrations.internal.financeiro.enabled', true) 
            && !empty($this->apiKey);
    }

    /**
     * Registra novo contrato para cobrança
     */
    public function registerContract(int $contractId, array $contractData): ?array
    {
        return $this->post('contracts', [
            'contract_id' => $contractId,
            'client_id' => $contractData['client_id'],
            'client_name' => $contractData['client_name'],
            'service_type' => $contractData['service_type'],
            'monthly_value' => $contractData['monthly_value'],
            'start_date' => $contractData['start_date'],
            'end_date' => $contractData['end_date'] ?? null,
            'billing_day' => $contractData['billing_day'] ?? 1,
            'payment_method' => $contractData['payment_method'] ?? 'boleto',
        ]);
    }

    /**
     * Atualiza dados do contrato
     */
    public function updateContract(int $contractId, array $data): ?array
    {
        return $this->put("contracts/{$contractId}", $data);
    }

    /**
     * Notifica encerramento de contrato
     */
    public function closeContract(int $contractId, string $endDate): ?array
    {
        return $this->post("contracts/{$contractId}/close", [
            'end_date' => $endDate,
            'closed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Obtém status financeiro do cliente
     */
    public function getClientFinancialStatus(int $clientId): ?array
    {
        return $this->get("clients/{$clientId}/financial-status");
    }

    /**
     * Verifica se cliente tem pagamentos em atraso
     */
    public function checkOverduePayments(int $clientId): ?array
    {
        return $this->get("clients/{$clientId}/overdue");
    }

    /**
     * Obtém previsão de faturamento
     */
    public function getRevenueForcast(string $startDate, string $endDate): ?array
    {
        return $this->get('forecast', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Registra alteração de valor do contrato
     */
    public function updateContractValue(int $contractId, float $newValue, string $effectiveDate): ?array
    {
        return $this->post("contracts/{$contractId}/value-change", [
            'new_value' => $newValue,
            'effective_date' => $effectiveDate,
        ]);
    }
}
