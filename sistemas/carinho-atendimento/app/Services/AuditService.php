<?php

namespace App\Services;

use App\Support\DomainLookup;
use Illuminate\Support\Facades\DB;

class AuditService
{
    public function __construct(
        private DomainLookup $domainLookup
    ) {
    }

    /**
     * Registra uma ação na auditoria
     */
    public function logAction(
        int $conversationId,
        string $actionTypeCode,
        ?int $agentId = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $notes = null
    ): int {
        $actionTypeId = $this->domainLookup->actionTypeId($actionTypeCode);

        return DB::table('conversation_actions')->insertGetId([
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
     * Registra mudança de status
     */
    public function logStatusChange(
        int $conversationId,
        string $oldStatusCode,
        string $newStatusCode,
        ?int $agentId = null
    ): int {
        return $this->logAction(
            $conversationId,
            'status_change',
            $agentId,
            $oldStatusCode,
            $newStatusCode
        );
    }

    /**
     * Registra mudança de prioridade
     */
    public function logPriorityChange(
        int $conversationId,
        string $oldPriorityCode,
        string $newPriorityCode,
        ?int $agentId = null
    ): int {
        return $this->logAction(
            $conversationId,
            'priority_change',
            $agentId,
            $oldPriorityCode,
            $newPriorityCode
        );
    }

    /**
     * Registra atribuição de agente
     */
    public function logAssignment(
        int $conversationId,
        ?int $oldAgentId,
        ?int $newAgentId,
        ?int $performedByAgentId = null
    ): int {
        $oldAgentName = $oldAgentId ? DB::table('agents')->where('id', $oldAgentId)->value('name') : null;
        $newAgentName = $newAgentId ? DB::table('agents')->where('id', $newAgentId)->value('name') : null;

        return $this->logAction(
            $conversationId,
            'assignment',
            $performedByAgentId,
            $oldAgentName,
            $newAgentName
        );
    }

    /**
     * Registra adição de nota
     */
    public function logNoteAdded(int $conversationId, int $agentId, string $notePreview): int
    {
        return $this->logAction(
            $conversationId,
            'note_added',
            $agentId,
            null,
            substr($notePreview, 0, 100)
        );
    }

    /**
     * Registra violação de SLA
     */
    public function logSlaBreach(int $conversationId, string $breachType, int $elapsedMinutes, int $targetMinutes): int
    {
        return $this->logAction(
            $conversationId,
            'sla_breach',
            null,
            "Target: {$targetMinutes}min",
            "Atual: {$elapsedMinutes}min",
            "Tipo: {$breachType}"
        );
    }

    /**
     * Retorna o histórico de ações de uma conversa
     */
    public function getConversationHistory(int $conversationId): array
    {
        return DB::table('conversation_actions')
            ->join('domain_action_type', 'domain_action_type.id', '=', 'conversation_actions.action_type_id')
            ->leftJoin('agents', 'agents.id', '=', 'conversation_actions.agent_id')
            ->where('conversation_actions.conversation_id', $conversationId)
            ->select([
                'conversation_actions.id',
                'domain_action_type.code as action_type_code',
                'domain_action_type.label as action_type_label',
                'agents.name as agent_name',
                'conversation_actions.old_value',
                'conversation_actions.new_value',
                'conversation_actions.notes',
                'conversation_actions.created_at',
            ])
            ->orderByDesc('conversation_actions.created_at')
            ->get()
            ->toArray();
    }

    /**
     * Retorna ações recentes de um agente
     */
    public function getAgentRecentActions(int $agentId, int $limit = 50): array
    {
        return DB::table('conversation_actions')
            ->join('domain_action_type', 'domain_action_type.id', '=', 'conversation_actions.action_type_id')
            ->join('conversations', 'conversations.id', '=', 'conversation_actions.conversation_id')
            ->join('contacts', 'contacts.id', '=', 'conversations.contact_id')
            ->where('conversation_actions.agent_id', $agentId)
            ->select([
                'conversation_actions.id',
                'conversation_actions.conversation_id',
                'contacts.name as contact_name',
                'domain_action_type.code as action_type_code',
                'domain_action_type.label as action_type_label',
                'conversation_actions.old_value',
                'conversation_actions.new_value',
                'conversation_actions.created_at',
            ])
            ->orderByDesc('conversation_actions.created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Retorna estatísticas de ações por tipo em um período
     */
    public function getActionStats(string $startDate, string $endDate, ?int $agentId = null): array
    {
        $query = DB::table('conversation_actions')
            ->join('domain_action_type', 'domain_action_type.id', '=', 'conversation_actions.action_type_id')
            ->whereBetween('conversation_actions.created_at', [$startDate, $endDate])
            ->groupBy('domain_action_type.code', 'domain_action_type.label')
            ->select([
                'domain_action_type.code',
                'domain_action_type.label',
                DB::raw('COUNT(*) as total'),
            ]);

        if ($agentId) {
            $query->where('conversation_actions.agent_id', $agentId);
        }

        return $query->get()->toArray();
    }
}
