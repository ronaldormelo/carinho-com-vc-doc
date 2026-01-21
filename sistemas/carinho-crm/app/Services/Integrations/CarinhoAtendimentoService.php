<?php

namespace App\Services\Integrations;

/**
 * Integração com Carinho Atendimento (atendimento.carinho.com.vc)
 * Sincroniza status, interações e alertas
 */
class CarinhoAtendimentoService extends BaseInternalService
{
    protected string $serviceName = 'carinho-atendimento';

    public function isEnabled(): bool
    {
        return config('integrations.internal.atendimento.enabled', true) 
            && !empty($this->apiKey);
    }

    /**
     * Notifica novo lead para atendimento
     */
    public function notifyNewLead(int $leadId, array $leadData): ?array
    {
        return $this->post('leads/new', [
            'lead_id' => $leadId,
            'name' => $leadData['name'],
            'phone' => $leadData['phone'],
            'urgency' => $leadData['urgency'] ?? 'normal',
            'service_type' => $leadData['service_type'] ?? null,
            'source' => $leadData['source'] ?? 'crm',
            'created_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Atualiza status do lead no atendimento
     */
    public function updateLeadStatus(int $leadId, string $status, ?string $notes = null): ?array
    {
        return $this->put("leads/{$leadId}/status", [
            'status' => $status,
            'notes' => $notes,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Registra interação vinda do atendimento
     */
    public function syncInteraction(int $leadId, array $interactionData): ?array
    {
        return $this->post("leads/{$leadId}/interactions", [
            'channel' => $interactionData['channel'],
            'direction' => $interactionData['direction'] ?? 'outbound', // inbound/outbound
            'summary' => $interactionData['summary'],
            'agent_id' => $interactionData['agent_id'] ?? null,
            'occurred_at' => $interactionData['occurred_at'] ?? now()->toIso8601String(),
        ]);
    }

    /**
     * Obtém histórico de atendimento do lead
     */
    public function getLeadHistory(int $leadId): ?array
    {
        return $this->get("leads/{$leadId}/history");
    }

    /**
     * Envia alerta para equipe de atendimento
     */
    public function sendAlert(string $type, array $data): ?array
    {
        return $this->post('alerts', [
            'type' => $type,
            'priority' => $data['priority'] ?? 'normal',
            'lead_id' => $data['lead_id'] ?? null,
            'message' => $data['message'],
            'created_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Cria tarefa de follow-up no atendimento
     */
    public function createFollowUp(int $leadId, string $notes, \DateTime $dueAt): ?array
    {
        return $this->post('follow-ups', [
            'lead_id' => $leadId,
            'notes' => $notes,
            'due_at' => $dueAt->format('Y-m-d H:i:s'),
        ]);
    }
}
