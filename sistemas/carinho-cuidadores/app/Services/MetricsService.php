<?php

namespace App\Services;

use App\Models\Caregiver;
use App\Models\CaregiverAssignment;
use App\Models\CaregiverDocument;
use App\Models\CaregiverIncident;
use App\Models\CaregiverWorkload;
use App\Models\DomainCaregiverStatus;
use App\Models\DomainIncidentSeverity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Serviço de métricas e indicadores operacionais.
 * Implementa KPIs consolidados para gestão de cuidadores.
 */
class MetricsService
{
    /**
     * Retorna dashboard completo de indicadores.
     */
    public function getDashboard(): array
    {
        $cacheKey = config('cuidadores.cache.prefix') . '_dashboard';
        $cacheTtl = config('cuidadores.cache.ttl_seconds', 300);

        if (config('cuidadores.cache.enabled')) {
            return Cache::remember($cacheKey, $cacheTtl, fn () => $this->buildDashboard());
        }

        return $this->buildDashboard();
    }

    /**
     * Constrói dashboard de indicadores.
     */
    private function buildDashboard(): array
    {
        return [
            'overview' => $this->getOverview(),
            'activation_metrics' => $this->getActivationMetrics(),
            'occupancy_metrics' => $this->getOccupancyMetrics(),
            'quality_metrics' => $this->getQualityMetrics(),
            'alerts' => $this->getAlerts(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Visão geral do banco de cuidadores.
     */
    public function getOverview(): array
    {
        $total = Caregiver::count();
        $byStatus = Caregiver::select('status_id', DB::raw('COUNT(*) as count'))
            ->groupBy('status_id')
            ->with('status')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->status?->code => $row->count])
            ->toArray();

        return [
            'total_caregivers' => $total,
            'active' => $byStatus['active'] ?? 0,
            'pending' => $byStatus['pending'] ?? 0,
            'inactive' => $byStatus['inactive'] ?? 0,
            'blocked' => $byStatus['blocked'] ?? 0,
            'active_percentage' => $total > 0 
                ? round((($byStatus['active'] ?? 0) / $total) * 100, 1) 
                : 0,
        ];
    }

    /**
     * Métricas de ativação de cuidadores.
     */
    public function getActivationMetrics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        // Cadastros novos no período
        $newRegistrations = Caregiver::where('created_at', '>=', $startDate)->count();

        // Ativações no período
        $activations = DB::table('caregiver_status_history')
            ->join('domain_caregiver_status', 'caregiver_status_history.status_id', '=', 'domain_caregiver_status.id')
            ->where('domain_caregiver_status.code', 'active')
            ->where('caregiver_status_history.changed_at', '>=', $startDate)
            ->count();

        // Taxa de ativação
        $activationRate = $newRegistrations > 0 
            ? round(($activations / $newRegistrations) * 100, 1) 
            : 0;

        // Tempo médio para ativação (dias)
        $avgActivationTime = DB::table('caregivers')
            ->join('caregiver_status_history', function ($join) {
                $join->on('caregivers.id', '=', 'caregiver_status_history.caregiver_id')
                    ->whereExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('domain_caregiver_status')
                            ->whereColumn('domain_caregiver_status.id', 'caregiver_status_history.status_id')
                            ->where('domain_caregiver_status.code', 'active');
                    });
            })
            ->where('caregivers.created_at', '>=', $startDate)
            ->selectRaw('AVG(DATEDIFF(caregiver_status_history.changed_at, caregivers.created_at)) as avg_days')
            ->value('avg_days');

        return [
            'period_days' => $days,
            'new_registrations' => $newRegistrations,
            'activations' => $activations,
            'activation_rate' => $activationRate,
            'average_activation_time_days' => $avgActivationTime ? round($avgActivationTime, 1) : null,
        ];
    }

    /**
     * Métricas de ocupação/utilização.
     */
    public function getOccupancyMetrics(): array
    {
        $maxHours = config('cuidadores.operacional.max_weekly_hours', 44);
        $weekStart = now()->startOfWeek();

        // Cuidadores ativos
        $activeCaregivers = Caregiver::active()->count();

        // Total de horas disponíveis (todos os ativos)
        $totalAvailableHours = $activeCaregivers * $maxHours;

        // Total de horas trabalhadas na semana
        $currentWeekWorkload = CaregiverWorkload::currentWeek()
            ->selectRaw('SUM(hours_worked) as total_hours, SUM(hours_scheduled) as scheduled_hours')
            ->first();

        $workedHours = (float) ($currentWeekWorkload->total_hours ?? 0);
        $scheduledHours = (float) ($currentWeekWorkload->scheduled_hours ?? 0);

        // Taxa de ocupação atual
        $currentOccupancyRate = $totalAvailableHours > 0 
            ? round(($workedHours / $totalAvailableHours) * 100, 1) 
            : 0;

        // Taxa de ocupação projetada
        $projectedOccupancyRate = $totalAvailableHours > 0 
            ? round((($workedHours + $scheduledHours) / $totalAvailableHours) * 100, 1) 
            : 0;

        // Cuidadores ociosos (menos de 20% de ocupação)
        $idleCaregivers = CaregiverWorkload::currentWeek()
            ->where('hours_worked', '<', $maxHours * 0.2)
            ->count();

        // Cuidadores sobrecarregados
        $overloadedCaregivers = CaregiverWorkload::currentWeek()
            ->overloaded()
            ->count();

        return [
            'active_caregivers' => $activeCaregivers,
            'total_available_hours' => $totalAvailableHours,
            'hours_worked_current_week' => round($workedHours, 1),
            'hours_scheduled_current_week' => round($scheduledHours, 1),
            'current_occupancy_rate' => $currentOccupancyRate,
            'projected_occupancy_rate' => $projectedOccupancyRate,
            'target_occupancy_rate' => config('cuidadores.indicadores.target_occupancy_rate', 80),
            'idle_caregivers' => $idleCaregivers,
            'overloaded_caregivers' => $overloadedCaregivers,
        ];
    }

    /**
     * Métricas de qualidade.
     */
    public function getQualityMetrics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        // Média geral de avaliações
        $avgRating = DB::table('caregiver_ratings')
            ->where('created_at', '>=', $startDate)
            ->avg('score');

        // Distribuição de notas
        $ratingDistribution = DB::table('caregiver_ratings')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('score, COUNT(*) as count')
            ->groupBy('score')
            ->pluck('count', 'score')
            ->toArray();

        // Total de avaliações
        $totalRatings = array_sum($ratingDistribution);

        // Avaliações positivas (4-5)
        $positiveRatings = ($ratingDistribution[4] ?? 0) + ($ratingDistribution[5] ?? 0);
        $positiveRate = $totalRatings > 0 
            ? round(($positiveRatings / $totalRatings) * 100, 1) 
            : 0;

        // Ocorrências no período
        $totalIncidents = CaregiverIncident::recent($days)->count();
        $severeIncidents = CaregiverIncident::recent($days)->severe()->count();

        // Ocorrências por tipo
        $incidentsByType = CaregiverIncident::recent($days)
            ->selectRaw('incident_type, COUNT(*) as count')
            ->groupBy('incident_type')
            ->pluck('count', 'incident_type')
            ->toArray();

        // Cuidadores que precisam de atenção
        $needsAttentionCount = DB::table('caregivers')
            ->join('caregiver_ratings', 'caregivers.id', '=', 'caregiver_ratings.caregiver_id')
            ->join('domain_caregiver_status', 'caregivers.status_id', '=', 'domain_caregiver_status.id')
            ->where('domain_caregiver_status.code', 'active')
            ->groupBy('caregivers.id')
            ->havingRaw('AVG(caregiver_ratings.score) < ?', [config('cuidadores.avaliacoes.nota_alerta', 3)])
            ->havingRaw('COUNT(caregiver_ratings.id) >= 2')
            ->count();

        return [
            'period_days' => $days,
            'average_rating' => $avgRating ? round($avgRating, 2) : null,
            'total_ratings' => $totalRatings,
            'positive_rate' => $positiveRate,
            'rating_distribution' => $ratingDistribution,
            'total_incidents' => $totalIncidents,
            'severe_incidents' => $severeIncidents,
            'incidents_by_type' => $incidentsByType,
            'caregivers_needs_attention' => $needsAttentionCount,
        ];
    }

    /**
     * Retorna alertas operacionais ativos.
     */
    public function getAlerts(): array
    {
        $alerts = [];

        // Documentos vencendo
        $expiringDocs = CaregiverDocument::expiring(30)
            ->with('caregiver', 'docType')
            ->get();

        foreach ($expiringDocs as $doc) {
            $alerts[] = [
                'type' => 'document_expiring',
                'severity' => 'warning',
                'message' => "Documento '{$doc->docType?->label}' de {$doc->caregiver?->name} vence em {$doc->days_until_expiry} dias",
                'caregiver_id' => $doc->caregiver_id,
                'document_id' => $doc->id,
                'expires_at' => $doc->expires_at?->format('Y-m-d'),
            ];
        }

        // Documentos vencidos
        $expiredDocs = CaregiverDocument::expired()
            ->whereHas('caregiver', fn ($q) => $q->active())
            ->with('caregiver', 'docType')
            ->get();

        foreach ($expiredDocs as $doc) {
            $alerts[] = [
                'type' => 'document_expired',
                'severity' => 'critical',
                'message' => "Documento '{$doc->docType?->label}' de {$doc->caregiver?->name} está VENCIDO",
                'caregiver_id' => $doc->caregiver_id,
                'document_id' => $doc->id,
                'expires_at' => $doc->expires_at?->format('Y-m-d'),
            ];
        }

        // Cuidadores sobrecarregados
        $overloaded = CaregiverWorkload::currentWeek()
            ->overloaded()
            ->with('caregiver')
            ->get();

        foreach ($overloaded as $workload) {
            $alerts[] = [
                'type' => 'workload_exceeded',
                'severity' => 'warning',
                'message' => "{$workload->caregiver?->name} excedeu limite de horas ({$workload->hours_worked}h trabalhadas)",
                'caregiver_id' => $workload->caregiver_id,
                'hours_worked' => (float) $workload->hours_worked,
            ];
        }

        // Ocorrências graves recentes não resolvidas
        $unresolvedIncidents = CaregiverIncident::severe()
            ->pendingResolution()
            ->recent(7)
            ->with('caregiver', 'severity')
            ->get();

        foreach ($unresolvedIncidents as $incident) {
            $alerts[] = [
                'type' => 'incident_unresolved',
                'severity' => 'critical',
                'message' => "Ocorrência grave não resolvida: {$incident->type_label} - {$incident->caregiver?->name}",
                'caregiver_id' => $incident->caregiver_id,
                'incident_id' => $incident->id,
                'occurred_at' => $incident->occurred_at?->format('Y-m-d'),
            ];
        }

        // Ordena por severidade
        usort($alerts, function ($a, $b) {
            $severityOrder = ['critical' => 0, 'warning' => 1, 'info' => 2];
            return ($severityOrder[$a['severity']] ?? 2) <=> ($severityOrder[$b['severity']] ?? 2);
        });

        return [
            'total' => count($alerts),
            'critical' => count(array_filter($alerts, fn ($a) => $a['severity'] === 'critical')),
            'warning' => count(array_filter($alerts, fn ($a) => $a['severity'] === 'warning')),
            'items' => $alerts,
        ];
    }

    /**
     * Calcula tempo médio de reposição de cuidadores.
     */
    public function getReplacementMetrics(int $days = 90): array
    {
        // Esta métrica requer integração com o sistema de operação
        // para saber quando um cuidador foi substituído em um serviço
        
        // Por enquanto, retorna estrutura básica
        return [
            'period_days' => $days,
            'average_replacement_time_hours' => null,
            'replacements_needed' => 0,
            'replacements_completed' => 0,
            'target_replacement_days' => config('cuidadores.indicadores.max_replacement_days', 3),
            'note' => 'Métrica requer integração com sistema de operação',
        ];
    }

    /**
     * Retorna indicadores por cidade.
     */
    public function getMetricsByCity(): array
    {
        return Caregiver::select('city')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status_id = 2 THEN 1 ELSE 0 END) as active')
            ->groupBy('city')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'city' => $row->city,
                'total' => $row->total,
                'active' => $row->active,
                'active_rate' => $row->total > 0 
                    ? round(($row->active / $row->total) * 100, 1) 
                    : 0,
            ])
            ->toArray();
    }

    /**
     * Retorna indicadores por tipo de cuidado.
     */
    public function getMetricsByCareType(): array
    {
        return DB::table('caregiver_skills')
            ->join('domain_care_type', 'caregiver_skills.care_type_id', '=', 'domain_care_type.id')
            ->join('caregivers', 'caregiver_skills.caregiver_id', '=', 'caregivers.id')
            ->join('domain_caregiver_status', 'caregivers.status_id', '=', 'domain_caregiver_status.id')
            ->select('domain_care_type.code', 'domain_care_type.label')
            ->selectRaw('COUNT(DISTINCT caregiver_skills.caregiver_id) as total')
            ->selectRaw('SUM(CASE WHEN domain_caregiver_status.code = "active" THEN 1 ELSE 0 END) as active')
            ->groupBy('domain_care_type.code', 'domain_care_type.label')
            ->get()
            ->map(fn ($row) => [
                'code' => $row->code,
                'label' => $row->label,
                'total' => $row->total,
                'active' => $row->active,
            ])
            ->toArray();
    }
}
