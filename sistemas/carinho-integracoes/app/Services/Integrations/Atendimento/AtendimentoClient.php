<?php

namespace App\Services\Integrations\Atendimento;

use App\Services\Integrations\BaseClient;

/**
 * Cliente do Atendimento. Rotas reais: /api/inbox, /api/conversations/{id}/messages, /api/metrics/*.
 */
class AtendimentoClient extends BaseClient
{
    protected string $configKey = 'atendimento';

    public function createConversation(array $data): array
    {
        return $this->post('/api/inbox', [
            'phone' => $data['phone'] ?? null,
            'senderName' => $data['name'] ?? '',
            'body' => $data['initial_message'] ?? $data['body'] ?? '',
        ]);
    }

    public function findConversationByPhone(string $phone): array
    {
        return $this->get('/api/inbox', ['phone' => $phone]);
    }

    public function updateConversationStatus(int $conversationId, string $status): array
    {
        return $this->patch("/api/inbox/{$conversationId}/status", [
            'status' => $status,
        ]);
    }

    public function addMessage(int $conversationId, array $data): array
    {
        return $this->post("/api/conversations/{$conversationId}/messages", [
            'body' => $data['body'] ?? $data['content'] ?? '',
            'direction' => $data['direction'] ?? 'outbound',
            'media_url' => $data['media_url'] ?? null,
        ]);
    }

    public function getMessages(int $conversationId): array
    {
        return $this->get("/api/inbox/{$conversationId}");
    }

    public function getQueuePosition(int $conversationId): array
    {
        return $this->unsupported("fila da conversa {$conversationId}");
    }

    public function getWaitingConversations(): array
    {
        return $this->get('/api/inbox', ['status' => 'waiting']);
    }

    public function assignToAgent(int $conversationId, int $agentId): array
    {
        return $this->patch("/api/inbox/{$conversationId}/status", [
            'status' => 'in_progress',
            'assigned_to' => $agentId,
            'agent_id' => $agentId,
        ]);
    }

    public function addTag(int $conversationId, string $tag): array
    {
        return $this->post("/api/inbox/{$conversationId}/tags", [
            'tags' => [$tag],
        ]);
    }

    public function removeTag(int $conversationId, string $tag): array
    {
        return $this->unsupported("remover tag {$tag} da conversa {$conversationId}");
    }

    public function getMetrics(): array
    {
        return $this->get('/api/metrics/dashboard');
    }

    public function getAverageResponseTime(): array
    {
        return $this->get('/api/metrics/sla');
    }

    public function dispatchEvent(string $eventType, array $payload): array
    {
        return $this->unsupported("webhook de eventos ({$eventType})");
    }
}
