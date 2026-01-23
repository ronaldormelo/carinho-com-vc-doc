<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DomainLookup
{
    public function channelId(string $code): int
    {
        return $this->lookupId('domain_channel', $code);
    }

    public function conversationStatusId(string $code): int
    {
        return $this->lookupId('domain_conversation_status', $code);
    }

    public function priorityId(string $code): int
    {
        return $this->lookupId('domain_priority', $code);
    }

    public function messageDirectionId(string $code): int
    {
        return $this->lookupId('domain_message_direction', $code);
    }

    public function messageStatusId(string $code): int
    {
        return $this->lookupId('domain_message_status', $code);
    }

    public function agentRoleId(string $code): int
    {
        return $this->lookupId('domain_agent_role', $code);
    }

    public function incidentSeverityId(string $code): int
    {
        return $this->lookupId('domain_incident_severity', $code);
    }

    public function webhookStatusId(string $code): int
    {
        return $this->lookupId('domain_webhook_status', $code);
    }

    public function supportLevelId(string $code): int
    {
        return $this->lookupId('domain_support_level', $code);
    }

    public function lossReasonId(string $code): int
    {
        return $this->lookupId('domain_loss_reason', $code);
    }

    public function scriptCategoryId(string $code): int
    {
        return $this->lookupId('domain_script_category', $code);
    }

    public function actionTypeId(string $code): int
    {
        return $this->lookupId('domain_action_type', $code);
    }

    public function supportLevelByCode(string $code): ?object
    {
        return $this->lookupRecord('domain_support_level', $code);
    }

    public function lossReasonByCode(string $code): ?object
    {
        return $this->lookupRecord('domain_loss_reason', $code);
    }

    public function getSlaConfiguration(int $priorityId, int $supportLevelId): ?object
    {
        $cacheKey = "sla_config:{$priorityId}:{$supportLevelId}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($priorityId, $supportLevelId) {
            return DB::table('sla_configurations')
                ->where('priority_id', $priorityId)
                ->where('support_level_id', $supportLevelId)
                ->where('active', 1)
                ->first();
        });
    }

    private function lookupId(string $table, string $code): int
    {
        $cacheKey = "domain:{$table}:{$code}";

        $id = Cache::remember($cacheKey, now()->addHours(12), function () use ($table, $code) {
            return DB::table($table)->where('code', $code)->value('id');
        });

        if (!$id) {
            throw new RuntimeException("Domain code not found: {$table}:{$code}");
        }

        return (int) $id;
    }

    private function lookupRecord(string $table, string $code): ?object
    {
        $cacheKey = "domain_record:{$table}:{$code}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($table, $code) {
            return DB::table($table)->where('code', $code)->first();
        });
    }
}
