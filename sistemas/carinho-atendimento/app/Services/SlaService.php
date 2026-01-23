<?php

namespace App\Services;

use App\Repositories\AtendimentoRepository;
use App\Support\DomainLookup;
use Illuminate\Support\Facades\DB;

class SlaService
{
    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup
    ) {
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

        // Verifica se violou SLA de primeira resposta
        $this->checkFirstResponseSla($conversationId, (int) $responseTime);
    }

    public function markResolved(int $conversationId, ?string $resolvedAt = null): void
    {
        $resolvedAtValue = $resolvedAt ?: now()->toDateTimeString();

        $this->repository->updateSlaMetric($conversationId, [
            'resolved_at' => $resolvedAtValue,
        ]);

        // Verifica se violou SLA de resolução
        $this->checkResolutionSla($conversationId, $resolvedAtValue);
    }

    /**
     * Verifica SLA de primeira resposta
     */
    private function checkFirstResponseSla(int $conversationId, int $responseTimeSec): void
    {
        $conversation = $this->repository->findConversationById($conversationId);
        
        if (!$conversation) {
            return;
        }

        $slaConfig = $this->domainLookup->getSlaConfiguration(
            $conversation->priority_id,
            $conversation->support_level_id ?? 1
        );

        if (!$slaConfig) {
            return;
        }

        $targetSec = $slaConfig->max_first_response_minutes * 60;
        
        if ($responseTimeSec > $targetSec) {
            $this->createSlaAlert(
                $conversationId,
                'first_response_breach',
                $slaConfig->max_first_response_minutes,
                (int) ceil($responseTimeSec / 60)
            );
        }
    }

    /**
     * Verifica SLA de resolução
     */
    private function checkResolutionSla(int $conversationId, string $resolvedAt): void
    {
        $conversation = $this->repository->findConversationById($conversationId);
        
        if (!$conversation || !$conversation->started_at) {
            return;
        }

        $slaConfig = $this->domainLookup->getSlaConfiguration(
            $conversation->priority_id,
            $conversation->support_level_id ?? 1
        );

        if (!$slaConfig) {
            return;
        }

        $resolutionTimeSec = strtotime($resolvedAt) - strtotime($conversation->started_at);
        $targetSec = $slaConfig->max_resolution_minutes * 60;

        if ($resolutionTimeSec > $targetSec) {
            $this->createSlaAlert(
                $conversationId,
                'resolution_breach',
                $slaConfig->max_resolution_minutes,
                (int) ceil($resolutionTimeSec / 60)
            );
        }
    }

    /**
     * Cria um alerta de SLA
     */
    private function createSlaAlert(int $conversationId, string $alertType, int $thresholdMinutes, int $actualMinutes): void
    {
        DB::table('sla_alerts')->insert([
            'conversation_id' => $conversationId,
            'alert_type' => $alertType,
            'threshold_minutes' => $thresholdMinutes,
            'actual_minutes' => $actualMinutes,
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Retorna métricas de SLA de uma conversa
     */
    public function getSlaMetrics(int $conversationId): ?array
    {
        $sla = DB::table('sla_metrics')->where('conversation_id', $conversationId)->first();
        
        if (!$sla) {
            return null;
        }

        $conversation = $this->repository->findConversationById($conversationId);
        
        if (!$conversation) {
            return null;
        }

        $slaConfig = $this->domainLookup->getSlaConfiguration(
            $conversation->priority_id,
            $conversation->support_level_id ?? 1
        );

        $responseTimeMin = $sla->response_time_sec > 0 ? ceil($sla->response_time_sec / 60) : null;
        $resolutionTimeMin = null;

        if ($sla->resolved_at && $conversation->started_at) {
            $resolutionTimeMin = ceil((strtotime($sla->resolved_at) - strtotime($conversation->started_at)) / 60);
        }

        return [
            'first_response_at' => $sla->first_response_at,
            'response_time_minutes' => $responseTimeMin,
            'response_target_minutes' => $slaConfig?->max_first_response_minutes,
            'response_within_sla' => $slaConfig ? ($responseTimeMin <= $slaConfig->max_first_response_minutes) : null,
            'resolved_at' => $sla->resolved_at,
            'resolution_time_minutes' => $resolutionTimeMin,
            'resolution_target_minutes' => $slaConfig?->max_resolution_minutes,
            'resolution_within_sla' => $slaConfig && $resolutionTimeMin ? ($resolutionTimeMin <= $slaConfig->max_resolution_minutes) : null,
        ];
    }

    /**
     * Retorna conversas com SLA em risco (próximas de violar)
     */
    public function getConversationsAtRisk(): array
    {
        return DB::table('conversations')
            ->join('contacts', 'contacts.id', '=', 'conversations.contact_id')
            ->join('sla_configurations', function ($join) {
                $join->on('sla_configurations.priority_id', '=', 'conversations.priority_id')
                     ->on('sla_configurations.support_level_id', '=', 'conversations.support_level_id');
            })
            ->leftJoin('sla_metrics', 'sla_metrics.conversation_id', '=', 'conversations.id')
            ->whereNull('conversations.closed_at')
            ->where(function ($query) {
                // Sem primeira resposta e tempo > 80% do target
                $query->whereNull('sla_metrics.first_response_at')
                      ->whereRaw('TIMESTAMPDIFF(SECOND, conversations.started_at, NOW()) > sla_configurations.max_first_response_minutes * 60 * 0.8');
            })
            ->orWhere(function ($query) {
                // Tempo total > 80% do target de resolução
                $query->whereNull('conversations.closed_at')
                      ->whereRaw('TIMESTAMPDIFF(SECOND, conversations.started_at, NOW()) > sla_configurations.max_resolution_minutes * 60 * 0.8');
            })
            ->select([
                'conversations.id',
                'contacts.name as contact_name',
                'contacts.phone as contact_phone',
                'conversations.started_at',
                'sla_metrics.first_response_at',
                'sla_configurations.max_first_response_minutes',
                'sla_configurations.max_resolution_minutes',
                DB::raw('TIMESTAMPDIFF(MINUTE, conversations.started_at, NOW()) as elapsed_minutes'),
            ])
            ->orderByDesc(DB::raw('TIMESTAMPDIFF(MINUTE, conversations.started_at, NOW())'))
            ->get()
            ->toArray();
    }

    /**
     * Retorna alertas de SLA pendentes
     */
    public function getPendingAlerts(): array
    {
        return DB::table('sla_alerts')
            ->join('conversations', 'conversations.id', '=', 'sla_alerts.conversation_id')
            ->join('contacts', 'contacts.id', '=', 'conversations.contact_id')
            ->whereNull('sla_alerts.acknowledged_at')
            ->select([
                'sla_alerts.id',
                'sla_alerts.conversation_id',
                'contacts.name as contact_name',
                'sla_alerts.alert_type',
                'sla_alerts.threshold_minutes',
                'sla_alerts.actual_minutes',
                'sla_alerts.created_at',
            ])
            ->orderByDesc('sla_alerts.created_at')
            ->get()
            ->toArray();
    }

    /**
     * Reconhece um alerta de SLA
     */
    public function acknowledgeAlert(int $alertId, int $agentId): void
    {
        DB::table('sla_alerts')
            ->where('id', $alertId)
            ->update([
                'acknowledged_by' => $agentId,
                'acknowledged_at' => now()->toDateTimeString(),
            ]);
    }

    /**
     * Retorna estatísticas de SLA para um período
     */
    public function getSlaStats(string $startDate, string $endDate): array
    {
        $total = DB::table('sla_metrics')
            ->join('conversations', 'conversations.id', '=', 'sla_metrics.conversation_id')
            ->whereBetween('conversations.started_at', [$startDate, $endDate])
            ->count();

        $responseWithinSla = DB::table('sla_metrics')
            ->join('conversations', 'conversations.id', '=', 'sla_metrics.conversation_id')
            ->join('sla_configurations', function ($join) {
                $join->on('sla_configurations.priority_id', '=', 'conversations.priority_id')
                     ->on('sla_configurations.support_level_id', '=', 'conversations.support_level_id');
            })
            ->whereBetween('conversations.started_at', [$startDate, $endDate])
            ->whereNotNull('sla_metrics.first_response_at')
            ->whereRaw('sla_metrics.response_time_sec <= sla_configurations.max_first_response_minutes * 60')
            ->count();

        $resolutionWithinSla = DB::table('sla_metrics')
            ->join('conversations', 'conversations.id', '=', 'sla_metrics.conversation_id')
            ->join('sla_configurations', function ($join) {
                $join->on('sla_configurations.priority_id', '=', 'conversations.priority_id')
                     ->on('sla_configurations.support_level_id', '=', 'conversations.support_level_id');
            })
            ->whereBetween('conversations.started_at', [$startDate, $endDate])
            ->whereNotNull('sla_metrics.resolved_at')
            ->whereRaw('TIMESTAMPDIFF(SECOND, conversations.started_at, sla_metrics.resolved_at) <= sla_configurations.max_resolution_minutes * 60')
            ->count();

        $avgResponseTime = DB::table('sla_metrics')
            ->join('conversations', 'conversations.id', '=', 'sla_metrics.conversation_id')
            ->whereBetween('conversations.started_at', [$startDate, $endDate])
            ->whereNotNull('sla_metrics.first_response_at')
            ->avg('sla_metrics.response_time_sec');

        return [
            'total_conversations' => $total,
            'response_within_sla' => $responseWithinSla,
            'response_sla_rate' => $total > 0 ? round(($responseWithinSla / $total) * 100, 1) : 0,
            'resolution_within_sla' => $resolutionWithinSla,
            'resolution_sla_rate' => $total > 0 ? round(($resolutionWithinSla / $total) * 100, 1) : 0,
            'avg_response_time_minutes' => $avgResponseTime ? round($avgResponseTime / 60, 1) : 0,
        ];
    }
}
