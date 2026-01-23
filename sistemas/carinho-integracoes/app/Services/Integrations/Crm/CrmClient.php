<?php

namespace App\Services\Integrations\Crm;

use App\Services\Integrations\BaseClient;

/**
 * Cliente para integracao com o sistema CRM (crm.carinho.com.vc).
 *
 * Responsavel por:
 * - Criacao e atualizacao de leads
 * - Registro de interacoes
 * - Sincronizacao de status de clientes
 * - Consulta de pipeline e deals
 */
class CrmClient extends BaseClient
{
    protected string $configKey = 'crm';

    /*
    |--------------------------------------------------------------------------
    | Leads
    |--------------------------------------------------------------------------
    */

    /**
     * Cria novo lead no CRM.
     */
    public function createLead(array $data): array
    {
        return $this->post('/api/v1/leads', $data);
    }

    /**
     * Atualiza lead existente.
     */
    public function updateLead(int $leadId, array $data): array
    {
        return $this->put("/api/v1/leads/{$leadId}", $data);
    }

    /**
     * Busca lead por ID.
     */
    public function getLead(int $leadId): array
    {
        return $this->get("/api/v1/leads/{$leadId}");
    }

    /**
     * Busca lead por telefone.
     */
    public function findLeadByPhone(string $phone): array
    {
        return $this->get('/api/v1/leads', ['phone' => $phone]);
    }

    /**
     * Busca lead por email.
     */
    public function findLeadByEmail(string $email): array
    {
        return $this->get('/api/v1/leads', ['email' => $email]);
    }

    /**
     * Avanca status do lead no pipeline.
     */
    public function advanceLead(int $leadId): array
    {
        return $this->post("/api/v1/leads/{$leadId}/advance");
    }

    /**
     * Marca lead como perdido.
     */
    public function markLeadAsLost(int $leadId, string $reason): array
    {
        return $this->post("/api/v1/leads/{$leadId}/lost", [
            'reason' => $reason,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Clientes
    |--------------------------------------------------------------------------
    */

    /**
     * Cria novo cliente.
     */
    public function createClient(array $data): array
    {
        return $this->post('/api/v1/clients', $data);
    }

    /**
     * Atualiza cliente existente.
     */
    public function updateClient(int $clientId, array $data): array
    {
        return $this->put("/api/v1/clients/{$clientId}", $data);
    }

    /**
     * Busca cliente por ID.
     */
    public function getClient(int $clientId): array
    {
        return $this->get("/api/v1/clients/{$clientId}");
    }

    /**
     * Registra necessidade de cuidado do cliente.
     */
    public function addCareNeed(int $clientId, array $data): array
    {
        return $this->post("/api/v1/clients/{$clientId}/care-needs", $data);
    }

    /**
     * Registra consentimento LGPD.
     */
    public function registerConsent(int $clientId, array $data): array
    {
        return $this->post("/api/v1/clients/{$clientId}/consents", $data);
    }

    /*
    |--------------------------------------------------------------------------
    | Interacoes
    |--------------------------------------------------------------------------
    */

    /**
     * Registra interacao no historico.
     */
    public function registerInteraction(int $leadId, array $data): array
    {
        return $this->post("/api/v1/leads/{$leadId}/interactions", [
            'channel' => $data['channel'] ?? 'whatsapp',
            'direction' => $data['direction'] ?? 'inbound',
            'content' => $data['content'] ?? '',
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    /**
     * Busca historico de interacoes.
     */
    public function getInteractions(int $leadId): array
    {
        return $this->get("/api/v1/leads/{$leadId}/interactions");
    }

    /*
    |--------------------------------------------------------------------------
    | Pipeline e Deals
    |--------------------------------------------------------------------------
    */

    /**
     * Lista estagios do pipeline.
     */
    public function getPipelineStages(): array
    {
        return $this->getCached('/api/v1/pipeline/stages');
    }

    /**
     * Busca metricas do pipeline.
     */
    public function getPipelineMetrics(): array
    {
        return $this->get('/api/v1/pipeline/metrics');
    }

    /**
     * Cria deal para lead.
     */
    public function createDeal(int $leadId, array $data): array
    {
        return $this->post('/api/v1/deals', array_merge($data, [
            'lead_id' => $leadId,
        ]));
    }

    /**
     * Marca deal como ganho.
     */
    public function markDealAsWon(int $dealId): array
    {
        return $this->post("/api/v1/deals/{$dealId}/won");
    }

    /*
    |--------------------------------------------------------------------------
    | Contratos
    |--------------------------------------------------------------------------
    */

    /**
     * Busca contrato por ID.
     */
    public function getContract(int $contractId): array
    {
        return $this->get("/api/v1/contracts/{$contractId}");
    }

    /**
     * Lista contratos do cliente.
     */
    public function getClientContracts(int $clientId): array
    {
        return $this->get('/api/v1/contracts', ['client_id' => $clientId]);
    }

    /**
     * Registra assinatura de contrato.
     */
    public function registerContractSignature(int $contractId, array $data): array
    {
        return $this->post("/api/v1/contracts/{$contractId}/sign", $data);
    }

    /*
    |--------------------------------------------------------------------------
    | Tarefas
    |--------------------------------------------------------------------------
    */

    /**
     * Cria tarefa de follow-up.
     */
    public function createTask(array $data): array
    {
        return $this->post('/api/v1/tasks', $data);
    }

    /**
     * Lista tarefas pendentes de um lead.
     */
    public function getLeadTasks(int $leadId): array
    {
        return $this->get('/api/v1/tasks', ['lead_id' => $leadId]);
    }

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */

    /**
     * Dispara evento para CRM.
     */
    public function dispatchEvent(string $eventType, array $payload): array
    {
        return $this->post('/api/v1/webhooks/events', [
            'event_type' => $eventType,
            'payload' => $payload,
            'source' => 'integracoes',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
