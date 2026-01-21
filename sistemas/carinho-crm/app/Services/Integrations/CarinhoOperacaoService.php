<?php

namespace App\Services\Integrations;

/**
 * Integração com Carinho Operação (operacao.carinho.com.vc)
 * Repassa dados para alocação de cuidadores e agenda
 */
class CarinhoOperacaoService extends BaseInternalService
{
    protected string $serviceName = 'carinho-operacao';

    public function isEnabled(): bool
    {
        return config('integrations.internal.operacao.enabled', true) 
            && !empty($this->apiKey);
    }

    /**
     * Notifica novo cliente para alocação
     */
    public function notifyNewClient(int $clientId, array $clientData): ?array
    {
        return $this->post('clients/new', [
            'client_id' => $clientId,
            'name' => $clientData['name'],
            'phone' => $clientData['phone'],
            'city' => $clientData['city'],
            'address' => $clientData['address'] ?? null,
            'care_needs' => $clientData['care_needs'] ?? [],
            'service_type' => $clientData['service_type'] ?? null,
            'start_date' => $clientData['start_date'] ?? null,
            'created_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Envia dados do contrato para operação
     */
    public function syncContract(int $contractId, array $contractData): ?array
    {
        return $this->post('contracts/sync', [
            'contract_id' => $contractId,
            'client_id' => $contractData['client_id'],
            'service_type' => $contractData['service_type'],
            'start_date' => $contractData['start_date'],
            'end_date' => $contractData['end_date'] ?? null,
            'monthly_hours' => $contractData['monthly_hours'] ?? null,
            'special_requirements' => $contractData['special_requirements'] ?? null,
        ]);
    }

    /**
     * Atualiza informações do cliente na operação
     */
    public function updateClient(int $clientId, array $data): ?array
    {
        return $this->put("clients/{$clientId}", $data);
    }

    /**
     * Obtém status de alocação do cliente
     */
    public function getClientAllocationStatus(int $clientId): ?array
    {
        return $this->get("clients/{$clientId}/allocation");
    }

    /**
     * Obtém agenda de serviços do cliente
     */
    public function getClientSchedule(int $clientId): ?array
    {
        return $this->get("clients/{$clientId}/schedule");
    }

    /**
     * Notifica cancelamento de contrato
     */
    public function notifyContractCancellation(int $contractId, string $reason): ?array
    {
        return $this->post("contracts/{$contractId}/cancel", [
            'reason' => $reason,
            'cancelled_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Solicita cuidador substituto
     */
    public function requestSubstitute(int $clientId, string $date, string $reason): ?array
    {
        return $this->post('substitutes/request', [
            'client_id' => $clientId,
            'date' => $date,
            'reason' => $reason,
        ]);
    }
}
