<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class AtendimentoRepository
{
    public function findContactByPhone(string $phone): ?object
    {
        return DB::table('contacts')->where('phone', $phone)->first();
    }

    public function findContactById(int $contactId): ?object
    {
        return DB::table('contacts')->where('id', $contactId)->first();
    }

    public function createContact(array $data): int
    {
        return DB::table('contacts')->insertGetId($data);
    }

    public function updateContact(int $contactId, array $data): void
    {
        DB::table('contacts')->where('id', $contactId)->update($data);
    }

    public function findOpenConversation(int $contactId): ?object
    {
        return DB::table('conversations')
            ->where('contact_id', $contactId)
            ->whereNull('closed_at')
            ->orderByDesc('id')
            ->first();
    }

    public function findConversationById(int $conversationId): ?object
    {
        return DB::table('conversations')->where('id', $conversationId)->first();
    }

    public function createConversation(array $data): int
    {
        // Garantir que support_level_id tenha valor padrão
        if (!isset($data['support_level_id'])) {
            $data['support_level_id'] = 1; // N1 - Atendimento
        }
        
        return DB::table('conversations')->insertGetId($data);
    }

    public function updateConversation(int $conversationId, array $data): void
    {
        DB::table('conversations')->where('id', $conversationId)->update($data);
    }

    public function createMessage(array $data): int
    {
        return DB::table('messages')->insertGetId($data);
    }

    public function updateMessageStatus(int $messageId, int $statusId, ?string $sentAt = null): void
    {
        $payload = ['status_id' => $statusId];

        if ($sentAt) {
            $payload['sent_at'] = $sentAt;
        }

        DB::table('messages')->where('id', $messageId)->update($payload);
    }

    public function upsertTag(string $name): int
    {
        $tagId = DB::table('tags')->where('name', $name)->value('id');

        if ($tagId) {
            return (int) $tagId;
        }

        return DB::table('tags')->insertGetId(['name' => $name]);
    }

    public function attachTag(int $conversationId, int $tagId): void
    {
        DB::table('conversation_tags')->updateOrInsert([
            'conversation_id' => $conversationId,
            'tag_id' => $tagId,
        ], []);
    }

    public function findAutoRuleTemplate(string $triggerKey): ?object
    {
        return DB::table('auto_rules')
            ->join('message_templates', 'auto_rules.template_id', '=', 'message_templates.id')
            ->where('auto_rules.trigger_key', $triggerKey)
            ->where('auto_rules.enabled', 1)
            ->select('message_templates.*')
            ->first();
    }

    public function createSlaMetricIfMissing(int $conversationId): void
    {
        $exists = DB::table('sla_metrics')->where('conversation_id', $conversationId)->exists();

        if (!$exists) {
            DB::table('sla_metrics')->insert([
                'conversation_id' => $conversationId,
                'first_response_at' => null,
                'response_time_sec' => 0,
                'resolved_at' => null,
            ]);
        }
    }

    public function updateSlaMetric(int $conversationId, array $data): void
    {
        DB::table('sla_metrics')->where('conversation_id', $conversationId)->update($data);
    }

    public function createIncident(array $data): int
    {
        return DB::table('incidents')->insertGetId($data);
    }

    public function createWebhookEvent(array $data): int
    {
        return DB::table('webhook_events')->insertGetId($data);
    }

    public function updateWebhookEvent(int $webhookEventId, array $data): void
    {
        DB::table('webhook_events')->where('id', $webhookEventId)->update($data);
    }

    /**
     * Retorna agentes disponíveis por nível de suporte
     */
    public function getAvailableAgentsByLevel(int $supportLevelId): array
    {
        return DB::table('agents')
            ->leftJoin('conversations', function ($join) {
                $join->on('agents.id', '=', 'conversations.assigned_to')
                     ->whereNull('conversations.closed_at');
            })
            ->where('agents.support_level_id', $supportLevelId)
            ->where('agents.active', 1)
            ->groupBy('agents.id', 'agents.name', 'agents.email', 'agents.max_concurrent_conversations')
            ->havingRaw('COUNT(conversations.id) < agents.max_concurrent_conversations')
            ->select([
                'agents.id',
                'agents.name',
                'agents.email',
                DB::raw('COUNT(conversations.id) as current_conversations'),
                'agents.max_concurrent_conversations',
            ])
            ->orderBy(DB::raw('COUNT(conversations.id)'))
            ->get()
            ->toArray();
    }

    /**
     * Conta conversas ativas de um agente
     */
    public function countActiveConversations(int $agentId): int
    {
        return DB::table('conversations')
            ->where('assigned_to', $agentId)
            ->whereNull('closed_at')
            ->count();
    }

    /**
     * Retorna conversas sem agente atribuído
     */
    public function getUnassignedConversations(?int $supportLevelId = null): array
    {
        $query = DB::table('conversations')
            ->join('contacts', 'contacts.id', '=', 'conversations.contact_id')
            ->whereNull('conversations.assigned_to')
            ->whereNull('conversations.closed_at');

        if ($supportLevelId) {
            $query->where('conversations.support_level_id', $supportLevelId);
        }

        return $query->select([
                'conversations.id',
                'conversations.support_level_id',
                'conversations.priority_id',
                'conversations.started_at',
                'contacts.name as contact_name',
                'contacts.phone as contact_phone',
            ])
            ->orderBy('conversations.priority_id', 'desc')
            ->orderBy('conversations.started_at')
            ->get()
            ->toArray();
    }
}
