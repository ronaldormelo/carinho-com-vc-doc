<?php

namespace App\Services\Integrations\Atendimento;

use App\Services\Integrations\BaseClient;

/**
 * Cliente para integracao com o sistema de Atendimento (atendimento.carinho.com.vc).
 *
 * Responsavel por:
 * - Sincronizacao de conversas
 * - Status de atendimento
 * - Filas e distribuicao
 */
class AtendimentoClient extends BaseClient
{
    protected string $configKey = 'atendimento';

    /*
    |--------------------------------------------------------------------------
    | Conversas
    |--------------------------------------------------------------------------
    */

    /**
     * Cria nova conversa.
     */
    public function createConversation(array $data): array
    {
        return $this->post('/api/v1/conversations', $data);
    }

    /**
     * Busca conversa por telefone.
     */
    public function findConversationByPhone(string $phone): array
    {
        return $this->get('/api/v1/conversations', ['phone' => $phone]);
    }

    /**
     * Atualiza status da conversa.
     */
    public function updateConversationStatus(int $conversationId, string $status): array
    {
        return $this->put("/api/v1/conversations/{$conversationId}/status", [
            'status' => $status,
        ]);
    }

    /**
     * Adiciona mensagem a conversa.
     */
    public function addMessage(int $conversationId, array $data): array
    {
        return $this->post("/api/v1/conversations/{$conversationId}/messages", $data);
    }

    /**
     * Busca historico de mensagens.
     */
    public function getMessages(int $conversationId): array
    {
        return $this->get("/api/v1/conversations/{$conversationId}/messages");
    }

    /*
    |--------------------------------------------------------------------------
    | Filas
    |--------------------------------------------------------------------------
    */

    /**
     * Busca posicao na fila.
     */
    public function getQueuePosition(int $conversationId): array
    {
        return $this->get("/api/v1/conversations/{$conversationId}/queue");
    }

    /**
     * Lista conversas em espera.
     */
    public function getWaitingConversations(): array
    {
        return $this->get('/api/v1/conversations', ['status' => 'waiting']);
    }

    /**
     * Atribui conversa a atendente.
     */
    public function assignToAgent(int $conversationId, int $agentId): array
    {
        return $this->post("/api/v1/conversations/{$conversationId}/assign", [
            'agent_id' => $agentId,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Etiquetas
    |--------------------------------------------------------------------------
    */

    /**
     * Adiciona etiqueta a conversa.
     */
    public function addTag(int $conversationId, string $tag): array
    {
        return $this->post("/api/v1/conversations/{$conversationId}/tags", [
            'tag' => $tag,
        ]);
    }

    /**
     * Remove etiqueta da conversa.
     */
    public function removeTag(int $conversationId, string $tag): array
    {
        return $this->delete("/api/v1/conversations/{$conversationId}/tags/{$tag}");
    }

    /*
    |--------------------------------------------------------------------------
    | Metricas
    |--------------------------------------------------------------------------
    */

    /**
     * Busca metricas de atendimento.
     */
    public function getMetrics(): array
    {
        return $this->get('/api/v1/metrics');
    }

    /**
     * Busca tempo medio de resposta.
     */
    public function getAverageResponseTime(): array
    {
        return $this->getCached('/api/v1/metrics/response-time');
    }

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */

    /**
     * Dispara evento para Atendimento.
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
