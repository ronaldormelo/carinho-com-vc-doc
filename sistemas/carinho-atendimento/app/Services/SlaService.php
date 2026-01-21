<?php

namespace App\Services;

use App\Repositories\AtendimentoRepository;
use Illuminate\Support\Facades\DB;

class SlaService
{
    public function __construct(private AtendimentoRepository $repository)
    {
    }

    public function recordInbound(int $conversationId): void
    {
        $this->repository->createSlaMetricIfMissing($conversationId);
    }

    public function recordFirstResponse(int $conversationId, ?string $sentAt = null): void
    {
        $sla = DB::table('sla_metrics')->where('conversation_id', $conversationId)->first();

        if (!$sla || $sla->first_response_at) {
            return;
        }

        $startedAt = DB::table('conversations')->where('id', $conversationId)->value('started_at');

        if (!$startedAt) {
            return;
        }

        $sentAtValue = $sentAt ?: now()->toDateTimeString();
        $responseTime = strtotime($sentAtValue) - strtotime($startedAt);

        $this->repository->updateSlaMetric($conversationId, [
            'first_response_at' => $sentAtValue,
            'response_time_sec' => max(0, (int) $responseTime),
        ]);
    }

    public function markResolved(int $conversationId, ?string $resolvedAt = null): void
    {
        $this->repository->updateSlaMetric($conversationId, [
            'resolved_at' => $resolvedAt ?: now()->toDateTimeString(),
        ]);
    }
}
