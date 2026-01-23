<?php

namespace App\Services;

use App\Jobs\NotifyOperacaoJob;
use App\Repositories\AtendimentoRepository;
use Illuminate\Support\Facades\DB;

/**
 * Servico de controle de SLA (Service Level Agreement).
 *
 * Metas de resposta por prioridade:
 * - Urgente: 5 minutos para primeira resposta, 60 minutos para resolucao
 * - Alta: 15 minutos para primeira resposta, 120 minutos para resolucao
 * - Normal: 30 minutos para primeira resposta, 240 minutos para resolucao
 * - Baixa: 60 minutos para primeira resposta, 480 minutos para resolucao
 */
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

    /**
     * Obtem as metas de SLA para uma prioridade.
     */
    public function getTargets(int $priorityId): ?object
    {
        return DB::table('sla_targets')
            ->where('priority_id', $priorityId)
            ->first();
    }

    /**
     * Verifica se a primeira resposta esta dentro do SLA.
     */
    public function isFirstResponseWithinSla(int $conversationId): bool
    {
        $conversation = DB::table('conversations')
            ->where('id', $conversationId)
            ->first();

        if (!$conversation) {
            return false;
        }

        $sla = DB::table('sla_metrics')
            ->where('conversation_id', $conversationId)
            ->first();

        if (!$sla || !$sla->first_response_at) {
            return false;
        }

        $target = $this->getTargets($conversation->priority_id);

        if (!$target) {
            return true; // Sem meta definida, considera dentro do SLA
        }

        $targetSeconds = $target->first_response_minutes * 60;

        return $sla->response_time_sec <= $targetSeconds;
    }

    /**
     * Verifica conversas com SLA em risco ou violado.
     */
    public function checkSlaViolations(): array
    {
        $violations = [
            'at_risk' => [],
            'violated' => [],
        ];

        $openConversations = DB::table('conversations')
            ->join('sla_metrics', 'sla_metrics.conversation_id', '=', 'conversations.id')
            ->whereNull('conversations.closed_at')
            ->whereNull('sla_metrics.first_response_at')
            ->select([
                'conversations.id',
                'conversations.priority_id',
                'conversations.started_at',
                'conversations.contact_id',
            ])
            ->get();

        foreach ($openConversations as $conv) {
            $target = $this->getTargets($conv->priority_id);

            if (!$target) {
                continue;
            }

            $elapsedMinutes = now()->diffInMinutes($conv->started_at);
            $targetMinutes = $target->first_response_minutes;

            // Em risco: 80% do tempo ja passou
            if ($elapsedMinutes >= ($targetMinutes * 0.8) && $elapsedMinutes < $targetMinutes) {
                $violations['at_risk'][] = [
                    'conversation_id' => $conv->id,
                    'elapsed_minutes' => $elapsedMinutes,
                    'target_minutes' => $targetMinutes,
                    'remaining_minutes' => $targetMinutes - $elapsedMinutes,
                ];
            }

            // Violado: passou do limite
            if ($elapsedMinutes >= $targetMinutes) {
                $violations['violated'][] = [
                    'conversation_id' => $conv->id,
                    'elapsed_minutes' => $elapsedMinutes,
                    'target_minutes' => $targetMinutes,
                    'exceeded_by' => $elapsedMinutes - $targetMinutes,
                ];
            }
        }

        return $violations;
    }

    /**
     * Envia alertas para conversas com SLA em risco.
     */
    public function sendSlaAlerts(): int
    {
        $violations = $this->checkSlaViolations();
        $alertsSent = 0;

        // Alerta para violacoes
        foreach ($violations['violated'] as $violation) {
            NotifyOperacaoJob::dispatch([
                'type' => 'sla_violation',
                'conversation_id' => $violation['conversation_id'],
                'exceeded_by_minutes' => $violation['exceeded_by'],
                'severity' => 'high',
            ]);
            $alertsSent++;
        }

        return $alertsSent;
    }

    /**
     * Obtem metricas de SLA agregadas.
     */
    public function getMetrics(string $period = 'today'): array
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        $total = DB::table('sla_metrics')
            ->join('conversations', 'conversations.id', '=', 'sla_metrics.conversation_id')
            ->where('conversations.started_at', '>=', $startDate)
            ->count();

        $respondidos = DB::table('sla_metrics')
            ->join('conversations', 'conversations.id', '=', 'sla_metrics.conversation_id')
            ->where('conversations.started_at', '>=', $startDate)
            ->whereNotNull('sla_metrics.first_response_at')
            ->count();

        $avgResponseTime = DB::table('sla_metrics')
            ->join('conversations', 'conversations.id', '=', 'sla_metrics.conversation_id')
            ->where('conversations.started_at', '>=', $startDate)
            ->whereNotNull('sla_metrics.first_response_at')
            ->avg('sla_metrics.response_time_sec');

        $withinSla = DB::table('sla_metrics')
            ->join('conversations', 'conversations.id', '=', 'sla_metrics.conversation_id')
            ->join('sla_targets', 'sla_targets.priority_id', '=', 'conversations.priority_id')
            ->where('conversations.started_at', '>=', $startDate)
            ->whereNotNull('sla_metrics.first_response_at')
            ->whereRaw('sla_metrics.response_time_sec <= (sla_targets.first_response_minutes * 60)')
            ->count();

        $slaComplianceRate = $respondidos > 0 ? round(($withinSla / $respondidos) * 100, 1) : 0;

        return [
            'period' => $period,
            'total_conversations' => $total,
            'responded' => $respondidos,
            'pending_response' => $total - $respondidos,
            'avg_response_time_sec' => $avgResponseTime ? round($avgResponseTime) : 0,
            'avg_response_time_min' => $avgResponseTime ? round($avgResponseTime / 60, 1) : 0,
            'within_sla' => $withinSla,
            'sla_compliance_rate' => $slaComplianceRate,
        ];
    }

    /**
     * Obtem metricas por prioridade.
     */
    public function getMetricsByPriority(string $period = 'today'): array
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        $priorities = DB::table('domain_priority')->get();
        $metrics = [];

        foreach ($priorities as $priority) {
            $total = DB::table('sla_metrics')
                ->join('conversations', 'conversations.id', '=', 'sla_metrics.conversation_id')
                ->where('conversations.started_at', '>=', $startDate)
                ->where('conversations.priority_id', $priority->id)
                ->count();

            $avgResponse = DB::table('sla_metrics')
                ->join('conversations', 'conversations.id', '=', 'sla_metrics.conversation_id')
                ->where('conversations.started_at', '>=', $startDate)
                ->where('conversations.priority_id', $priority->id)
                ->whereNotNull('sla_metrics.first_response_at')
                ->avg('sla_metrics.response_time_sec');

            $target = $this->getTargets($priority->id);

            $metrics[$priority->code] = [
                'label' => $priority->label,
                'total' => $total,
                'avg_response_sec' => $avgResponse ? round($avgResponse) : 0,
                'target_response_minutes' => $target?->first_response_minutes ?? 0,
                'target_resolution_minutes' => $target?->resolution_minutes ?? 0,
            ];
        }

        return $metrics;
    }
}
