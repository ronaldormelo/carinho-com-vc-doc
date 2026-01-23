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

    public function updateIncident(int $incidentId, array $data): void
    {
        DB::table('incidents')->where('id', $incidentId)->update($data);
    }

    public function findIncidentById(int $incidentId): ?object
    {
        return DB::table('incidents')->where('id', $incidentId)->first();
    }

    public function createWebhookEvent(array $data): int
    {
        return DB::table('webhook_events')->insertGetId($data);
    }

    public function updateWebhookEvent(int $webhookEventId, array $data): void
    {
        DB::table('webhook_events')->where('id', $webhookEventId)->update($data);
    }

    public function createConversationHistory(array $data): int
    {
        return DB::table('conversation_history')->insertGetId($data);
    }

    public function getConversationHistory(int $conversationId): array
    {
        return DB::table('conversation_history')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    public function createSatisfactionSurvey(array $data): int
    {
        return DB::table('satisfaction_surveys')->insertGetId($data);
    }

    public function updateSatisfactionSurvey(int $surveyId, array $data): void
    {
        DB::table('satisfaction_surveys')->where('id', $surveyId)->update($data);
    }

    public function findSatisfactionSurveyByConversation(int $conversationId): ?object
    {
        return DB::table('satisfaction_surveys')
            ->where('conversation_id', $conversationId)
            ->first();
    }

    public function getTriageChecklist(): array
    {
        return DB::table('triage_checklist')
            ->where('active', 1)
            ->orderBy('item_order')
            ->get()
            ->toArray();
    }

    public function saveTriageResponse(int $conversationId, int $checklistId, array $data): int
    {
        $existing = DB::table('conversation_triage')
            ->where('conversation_id', $conversationId)
            ->where('checklist_id', $checklistId)
            ->first();

        if ($existing) {
            DB::table('conversation_triage')
                ->where('id', $existing->id)
                ->update($data);
            return (int) $existing->id;
        }

        return DB::table('conversation_triage')->insertGetId(array_merge([
            'conversation_id' => $conversationId,
            'checklist_id' => $checklistId,
        ], $data));
    }

    public function getTriageResponses(int $conversationId): array
    {
        return DB::table('conversation_triage')
            ->where('conversation_id', $conversationId)
            ->get()
            ->keyBy('checklist_id')
            ->toArray();
    }
}
