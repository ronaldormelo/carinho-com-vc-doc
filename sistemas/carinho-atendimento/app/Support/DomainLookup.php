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

    public function incidentCategoryId(string $code): int
    {
        return $this->lookupId('domain_incident_category', $code);
    }

    public function actionTypeId(string $code): int
    {
        return $this->lookupId('domain_action_type', $code);
    }

    public function getSupportLevel(string $code): ?object
    {
        return $this->lookupRow('domain_support_level', $code);
    }

    public function getSlaTaret(int $priorityId): ?object
    {
        $cacheKey = "sla_target:{$priorityId}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($priorityId) {
            return DB::table('sla_targets')->where('priority_id', $priorityId)->first();
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

    private function lookupRow(string $table, string $code): ?object
    {
        $cacheKey = "domain_row:{$table}:{$code}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($table, $code) {
            return DB::table($table)->where('code', $code)->first();
        });
    }
}
