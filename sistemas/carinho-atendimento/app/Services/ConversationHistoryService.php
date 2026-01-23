<?php

namespace App\Services;

use App\Support\DomainLookup;
use Illuminate\Support\Facades\DB;

/**
 * Servico de registro de historico de acoes nas conversas.
 *
 * Mantem trilha de auditoria completa para:
 * - Mudancas de status e prioridade
 * - Atribuicoes de atendentes
 * - Escalonamentos entre niveis
 * - Anotacoes internas
 * - Registro de incidentes
 */
class ConversationHistoryService
{
    public function __construct(private DomainLookup $domainLookup)
    {
    }

    /**
     * Registra uma acao no historico da conversa.
     */
    public function record(
        int $conversationId,
        string $actionTypeCode,
        ?int $agentId = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $notes = null
    ): int {
        $actionTypeId = $this->domainLookup->actionTypeId($actionTypeCode);

        return DB::table('conversation_history')->insertGetId([
            'conversation_id' => $conversationId,
            'action_type_id' => $actionTypeId,
            'agent_id' => $agentId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'notes' => $notes,
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Registra mudanca de status.
     */
    public function recordStatusChange(
        int $conversationId,
        string $oldStatus,
        string $newStatus,
        ?int $agentId = null
    ): int {
        return $this->record(
            $conversationId,
            'status_change',
            $agentId,
            $oldStatus,
            $newStatus
        );
    }

    /**
     * Registra mudanca de prioridade.
     */
    public function recordPriorityChange(
        int $conversationId,
        string $oldPriority,
        string $newPriority,
        ?int $agentId = null
    ): int {
        return $this->record(
            $conversationId,
            'priority_change',
            $agentId,
            $oldPriority,
            $newPriority
        );
    }

    /**
     * Registra atribuicao de atendente.
     */
    public function recordAssignment(
        int $conversationId,
        ?int $oldAgentId,
        int $newAgentId,
        ?int $assignedBy = null
    ): int {
        return $this->record(
            $conversationId,
            'assignment',
            $assignedBy,
            $oldAgentId ? (string) $oldAgentId : null,
            (string) $newAgentId
        );
    }

    /**
     * Registra anotacao interna.
     */
    public function recordNote(int $conversationId, string $note, ?int $agentId = null): int
    {
        return $this->record(
            $conversationId,
            'note',
            $agentId,
            null,
            null,
            $note
        );
    }

    /**
     * Registra encerramento.
     */
    public function recordClosure(
        int $conversationId,
        string $closureReason,
        ?int $agentId = null,
        ?string $notes = null
    ): int {
        return $this->record(
            $conversationId,
            'closure',
            $agentId,
            null,
            $closureReason,
            $notes
        );
    }

    /**
     * Obtem historico completo de uma conversa.
     */
    public function getHistory(int $conversationId): array
    {
        return DB::table('conversation_history')
            ->join('domain_action_type', 'domain_action_type.id', '=', 'conversation_history.action_type_id')
            ->leftJoin('agents', 'agents.id', '=', 'conversation_history.agent_id')
            ->where('conversation_history.conversation_id', $conversationId)
            ->orderBy('conversation_history.created_at', 'asc')
            ->select([
                'conversation_history.id',
                'domain_action_type.code as action_type',
                'domain_action_type.label as action_label',
                'agents.name as agent_name',
                'conversation_history.old_value',
                'conversation_history.new_value',
                'conversation_history.notes',
                'conversation_history.created_at',
            ])
            ->get()
            ->toArray();
    }

    /**
     * Obtem resumo de acoes por periodo.
     */
    public function getActionsSummary(string $period = 'today'): array
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        return DB::table('conversation_history')
            ->join('domain_action_type', 'domain_action_type.id', '=', 'conversation_history.action_type_id')
            ->where('conversation_history.created_at', '>=', $startDate)
            ->selectRaw('domain_action_type.code as action_type, COUNT(*) as total')
            ->groupBy('domain_action_type.code')
            ->get()
            ->pluck('total', 'action_type')
            ->toArray();
    }
}
