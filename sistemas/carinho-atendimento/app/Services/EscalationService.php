<?php

namespace App\Services;

use App\Repositories\AtendimentoRepository;
use App\Support\DomainLookup;
use Illuminate\Support\Facades\DB;

class EscalationService
{
    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup,
        private AuditService $auditService
    ) {
    }

    /**
     * Escalona uma conversa para o próximo nível de suporte
     */
    public function escalate(
        int $conversationId,
        string $reason,
        ?int $fromAgentId = null,
        ?int $toAgentId = null
    ): bool {
        $conversation = $this->repository->findConversationById($conversationId);

        if (!$conversation) {
            return false;
        }

        $currentLevelId = $conversation->support_level_id ?? 1;
        $nextLevelId = $this->getNextLevel($currentLevelId);

        if (!$nextLevelId || $nextLevelId === $currentLevelId) {
            return false; // Já está no nível máximo
        }

        $now = now()->toDateTimeString();

        // Registra o escalonamento
        DB::table('escalation_history')->insert([
            'conversation_id' => $conversationId,
            'from_level_id' => $currentLevelId,
            'to_level_id' => $nextLevelId,
            'from_agent_id' => $fromAgentId,
            'to_agent_id' => $toAgentId,
            'reason' => $reason,
            'escalated_at' => $now,
        ]);

        // Atualiza a conversa
        $updateData = [
            'support_level_id' => $nextLevelId,
            'updated_at' => $now,
        ];

        if ($toAgentId) {
            $updateData['assigned_to'] = $toAgentId;
        }

        $this->repository->updateConversation($conversationId, $updateData);

        // Registra na auditoria
        $this->auditService->logAction(
            $conversationId,
            'escalation',
            $fromAgentId,
            "N{$currentLevelId}",
            "N{$nextLevelId}",
            $reason
        );

        return true;
    }

    /**
     * Retorna o próximo nível de suporte
     */
    private function getNextLevel(int $currentLevelId): ?int
    {
        $levels = [1 => 2, 2 => 3, 3 => 3]; // N1 -> N2 -> N3 (N3 é o máximo)
        return $levels[$currentLevelId] ?? null;
    }

    /**
     * Verifica se uma conversa deve ser escalonada automaticamente
     * com base no SLA e tempo de resposta
     */
    public function checkAutoEscalation(int $conversationId): bool
    {
        $conversation = $this->repository->findConversationById($conversationId);

        if (!$conversation) {
            return false;
        }

        $slaConfig = $this->domainLookup->getSlaConfiguration(
            $conversation->priority_id,
            $conversation->support_level_id ?? 1
        );

        if (!$slaConfig) {
            return false;
        }

        // Verifica tempo desde o início da conversa
        $startedAt = strtotime($conversation->started_at);
        $now = time();
        $elapsedMinutes = ($now - $startedAt) / 60;

        // Se ultrapassou o tempo máximo de resolução, escalona
        if ($elapsedMinutes > $slaConfig->max_resolution_minutes) {
            $this->escalate(
                $conversationId,
                'Escalonamento automático por tempo de resolução excedido',
                null,
                $this->findAvailableAgent($conversation->support_level_id + 1)
            );
            return true;
        }

        // Verifica se atingiu o threshold de warning
        $warningMinutes = $slaConfig->max_resolution_minutes * ($slaConfig->warning_threshold_percent / 100);
        if ($elapsedMinutes > $warningMinutes && ($conversation->support_level_id ?? 1) < 3) {
            // Cria alerta sem escalonar
            $this->createSlaAlert($conversationId, 'warning', $slaConfig->max_resolution_minutes, (int)$elapsedMinutes);
        }

        return false;
    }

    /**
     * Encontra um agente disponível no nível de suporte especificado
     */
    private function findAvailableAgent(int $supportLevelId): ?int
    {
        // Busca agente com menos conversas ativas no nível especificado
        $agent = DB::table('agents')
            ->leftJoin('conversations', function ($join) {
                $join->on('agents.id', '=', 'conversations.assigned_to')
                     ->whereNull('conversations.closed_at');
            })
            ->where('agents.support_level_id', $supportLevelId)
            ->where('agents.active', 1)
            ->groupBy('agents.id')
            ->havingRaw('COUNT(conversations.id) < agents.max_concurrent_conversations')
            ->orderByRaw('COUNT(conversations.id) ASC')
            ->select('agents.id')
            ->first();

        return $agent?->id;
    }

    /**
     * Cria um alerta de SLA
     */
    private function createSlaAlert(int $conversationId, string $alertType, int $thresholdMinutes, int $actualMinutes): void
    {
        // Verifica se já existe alerta recente do mesmo tipo
        $existingAlert = DB::table('sla_alerts')
            ->where('conversation_id', $conversationId)
            ->where('alert_type', $alertType)
            ->where('created_at', '>', now()->subHours(1)->toDateTimeString())
            ->exists();

        if ($existingAlert) {
            return;
        }

        DB::table('sla_alerts')->insert([
            'conversation_id' => $conversationId,
            'alert_type' => $alertType,
            'threshold_minutes' => $thresholdMinutes,
            'actual_minutes' => $actualMinutes,
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Retorna o histórico de escalonamentos de uma conversa
     */
    public function getEscalationHistory(int $conversationId): array
    {
        return DB::table('escalation_history')
            ->join('domain_support_level as from_level', 'from_level.id', '=', 'escalation_history.from_level_id')
            ->join('domain_support_level as to_level', 'to_level.id', '=', 'escalation_history.to_level_id')
            ->leftJoin('agents as from_agent', 'from_agent.id', '=', 'escalation_history.from_agent_id')
            ->leftJoin('agents as to_agent', 'to_agent.id', '=', 'escalation_history.to_agent_id')
            ->where('escalation_history.conversation_id', $conversationId)
            ->select([
                'escalation_history.id',
                'from_level.label as from_level_label',
                'to_level.label as to_level_label',
                'from_agent.name as from_agent_name',
                'to_agent.name as to_agent_name',
                'escalation_history.reason',
                'escalation_history.escalated_at',
            ])
            ->orderBy('escalation_history.escalated_at')
            ->get()
            ->toArray();
    }

    /**
     * Retorna conversas que precisam de atenção (SLA em risco)
     */
    public function getConversationsAtRisk(): array
    {
        return DB::table('conversations')
            ->join('contacts', 'contacts.id', '=', 'conversations.contact_id')
            ->join('domain_priority', 'domain_priority.id', '=', 'conversations.priority_id')
            ->join('domain_support_level', 'domain_support_level.id', '=', 'conversations.support_level_id')
            ->join('sla_configurations', function ($join) {
                $join->on('sla_configurations.priority_id', '=', 'conversations.priority_id')
                     ->on('sla_configurations.support_level_id', '=', 'conversations.support_level_id');
            })
            ->whereNull('conversations.closed_at')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, conversations.started_at, NOW()) > sla_configurations.max_resolution_minutes * sla_configurations.warning_threshold_percent / 100')
            ->select([
                'conversations.id',
                'contacts.name as contact_name',
                'contacts.phone as contact_phone',
                'domain_priority.label as priority_label',
                'domain_support_level.label as support_level_label',
                'conversations.started_at',
                'sla_configurations.max_resolution_minutes',
                DB::raw('TIMESTAMPDIFF(MINUTE, conversations.started_at, NOW()) as elapsed_minutes'),
            ])
            ->orderByRaw('TIMESTAMPDIFF(MINUTE, conversations.started_at, NOW()) / sla_configurations.max_resolution_minutes DESC')
            ->get()
            ->toArray();
    }
}
