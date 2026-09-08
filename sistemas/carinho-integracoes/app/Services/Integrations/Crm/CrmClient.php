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
        return $this->post('/api/v1/public/leads', $data);
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
        return $this->get('/api/v1/leads/search', ['q' => $phone]);
    }

    /**
     * Busca lead por email.
     */
    public function findLeadByEmail(string $email): array
    {
        return $this->get('/api/v1/leads/search', ['q' => $email]);
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
        $channel = strtolower((string) ($data['channel'] ?? 'whatsapp'));
        $channelId = match ($channel) {
            'whatsapp' => 1,
            'email' => 2,
            'phone', 'telefone' => 3,
            default => 1,
        };

        $summary = trim((string) ($data['content'] ?? $data['summary'] ?? ''));
        if ($summary === '') {
            $summary = 'Interação registrada';
        }

        return $this->post('/webhooks/internal/atendimento/interaction', [
            'lead_id' => $leadId,
            'channel_id' => $data['channel_id'] ?? $channelId,
            'summary' => $summary,
            'occurred_at' => $data['occurred_at'] ?? now()->toIso8601String(),
        ]);
    }

    /**
     * Busca historico de interacoes.
     */
    public function getInteractions(int $leadId): array
    {
        return $this->get('/api/v1/interactions', ['lead_id' => $leadId]);
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
        $path = match ($eventType) {
            'service.started', 'service.scheduled' => '/webhooks/internal/operacao/service-started',
            'service.completed' => '/webhooks/internal/operacao/service-completed',
            'payment.received', 'payment.failed', 'invoice.created' => '/webhooks/internal/financeiro/payment',
            'lead.created' => '/webhooks/internal/site/lead',
            'contract.signed' => '/webhooks/internal/documentos/contract-signed',
            default => null,
        };

        if ($path === null) {
            return $this->unsupported("evento CRM {$eventType}");
        }

        return $this->post($path, $this->mapDispatchPayload($eventType, $payload));
    }

    private function mapDispatchPayload(string $eventType, array $payload): array
    {
        return match ($eventType) {
            'service.started', 'service.scheduled' => [
                'client_id' => $payload['client_id'] ?? null,
                'service_date' => $this->eventDate($payload),
                'caregiver_name' => $payload['caregiver_name'] ?? null,
            ],
            'service.completed' => [
                'client_id' => $payload['client_id'] ?? null,
                'service_date' => $this->eventDate($payload),
                'notes' => $payload['notes'] ?? null,
            ],
            'payment.received', 'payment.failed', 'invoice.created' => [
                'client_id' => $payload['client_id'] ?? null,
                'payment_status' => $payload['payment_status'] ?? match ($eventType) {
                    'payment.received' => 'paid',
                    'invoice.created' => 'pending',
                    default => 'pending',
                },
                'amount' => $payload['amount'] ?? 0,
                'reference_date' => $this->eventDate($payload),
            ],
            default => $payload,
        };
    }

    private function eventDate(array $payload): string
    {
        $raw = $payload['service_date']
            ?? $payload['reference_date']
            ?? $payload['completed_at']
            ?? $payload['timestamp']
            ?? now()->toDateString();

        return substr((string) $raw, 0, 10);
    }
}
