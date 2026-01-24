<?php

namespace App\Services;

use App\Models\SlaMetric;
use App\Models\SlaAlert;
use App\Models\Schedule;
use App\Models\Substitution;
use App\Models\Emergency;
use App\Models\Notification;
use App\Models\DomainScheduleStatus;
use App\Models\DomainNotificationStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para monitoramento de SLA operacional.
 * 
 * Calcula métricas, detecta violações e gera alertas proativos
 * para gestão de performance operacional.
 */
class SlaService
{
    /**
     * Configurações de SLA.
     */
    protected array $slaTargets;

    public function __construct()
    {
        $this->slaTargets = config('operacao.sla', []);
    }

    /**
     * Calcula e armazena métricas de SLA para uma data.
     */
    public function calculateDailyMetrics(?string $date = null): Collection
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::yesterday();
        $metrics = collect();

        // Pontualidade de check-in
        $metrics->push($this->calculateCheckinPunctuality($targetDate));

        // Taxa de substituição
        $metrics->push($this->calculateSubstitutionRate($targetDate));

        // Taxa de cancelamento
        $metrics->push($this->calculateCancellationRate($targetDate));

        // Tempo de resposta de emergência
        $metrics->push($this->calculateEmergencyResponse($targetDate));

        // Taxa de sucesso de notificações
        $metrics->push($this->calculateNotificationSuccess($targetDate));

        // Taxa de ocupação
        $metrics->push($this->calculateOccupancyRate($targetDate));

        // Verifica e gera alertas
        $metrics->each(fn($metric) => $this->checkAndCreateAlerts($metric));

        Log::info('Métricas de SLA calculadas', [
            'date' => $targetDate->toDateString(),
            'metrics_count' => $metrics->count(),
        ]);

        return $metrics;
    }

    /**
     * Calcula pontualidade de check-in.
     */
    protected function calculateCheckinPunctuality(Carbon $date): SlaMetric
    {
        $schedules = Schedule::onDate($date->toDateString())
            ->whereIn('status_id', [DomainScheduleStatus::IN_PROGRESS, DomainScheduleStatus::DONE])
            ->with('checkin')
            ->get();

        $total = $schedules->count();
        $onTime = 0;

        $toleranceMinutes = config('operacao.checkin.late_tolerance_minutes', 15);

        foreach ($schedules as $schedule) {
            if ($schedule->checkin) {
                $expectedTime = $schedule->start_date_time;
                $actualTime = Carbon::parse($schedule->checkin->timestamp);
                
                if ($actualTime->diffInMinutes($expectedTime, false) <= $toleranceMinutes) {
                    $onTime++;
                }
            }
        }

        $actualValue = $total > 0 ? ($onTime / $total) * 100 : 100;
        $targetValue = 95; // 95% de pontualidade

        return SlaMetric::updateOrCreate(
            [
                'metric_date' => $date->toDateString(),
                'metric_type' => SlaMetric::TYPE_CHECKIN_PUNCTUALITY,
                'dimension' => null,
                'dimension_value' => null,
            ],
            [
                'target_value' => $targetValue,
                'actual_value' => round($actualValue, 2),
                'target_met' => $actualValue >= $targetValue,
                'sample_size' => $total,
            ]
        );
    }

    /**
     * Calcula taxa de substituição.
     */
    protected function calculateSubstitutionRate(Carbon $date): SlaMetric
    {
        $totalAssignments = DB::table('assignments')
            ->whereDate('assigned_at', $date)
            ->count();

        $substitutions = Substitution::whereDate('created_at', $date)->count();

        $actualValue = $totalAssignments > 0 ? ($substitutions / $totalAssignments) * 100 : 0;
        $targetValue = 10; // Máximo 10% de substituição

        return SlaMetric::updateOrCreate(
            [
                'metric_date' => $date->toDateString(),
                'metric_type' => SlaMetric::TYPE_SUBSTITUTION_RATE,
                'dimension' => null,
                'dimension_value' => null,
            ],
            [
                'target_value' => $targetValue,
                'actual_value' => round($actualValue, 2),
                'target_met' => $actualValue <= $targetValue,
                'sample_size' => $totalAssignments,
            ]
        );
    }

    /**
     * Calcula taxa de cancelamento.
     */
    protected function calculateCancellationRate(Carbon $date): SlaMetric
    {
        $totalSchedules = Schedule::onDate($date->toDateString())->count();
        
        $canceled = Schedule::onDate($date->toDateString())
            ->where('status_id', DomainScheduleStatus::MISSED)
            ->count();

        $actualValue = $totalSchedules > 0 ? ($canceled / $totalSchedules) * 100 : 0;
        $targetValue = $this->slaTargets['max_cancellation_rate'] ?? 10;

        return SlaMetric::updateOrCreate(
            [
                'metric_date' => $date->toDateString(),
                'metric_type' => SlaMetric::TYPE_CANCELLATION_RATE,
                'dimension' => null,
                'dimension_value' => null,
            ],
            [
                'target_value' => $targetValue,
                'actual_value' => round($actualValue, 2),
                'target_met' => $actualValue <= $targetValue,
                'sample_size' => $totalSchedules,
            ]
        );
    }

    /**
     * Calcula tempo médio de resposta de emergência.
     */
    protected function calculateEmergencyResponse(Carbon $date): SlaMetric
    {
        $emergencies = Emergency::whereDate('created_at', $date)
            ->whereNotNull('resolved_at')
            ->get();

        $totalTime = 0;
        $count = $emergencies->count();

        foreach ($emergencies as $emergency) {
            $totalTime += $emergency->resolved_at->diffInMinutes($emergency->created_at);
        }

        $avgTime = $count > 0 ? $totalTime / $count : 0;
        $targetValue = 30; // 30 minutos máximo

        return SlaMetric::updateOrCreate(
            [
                'metric_date' => $date->toDateString(),
                'metric_type' => SlaMetric::TYPE_EMERGENCY_RESPONSE,
                'dimension' => null,
                'dimension_value' => null,
            ],
            [
                'target_value' => $targetValue,
                'actual_value' => round($avgTime, 2),
                'target_met' => $avgTime <= $targetValue,
                'sample_size' => $count,
            ]
        );
    }

    /**
     * Calcula taxa de sucesso de notificações.
     */
    protected function calculateNotificationSuccess(Carbon $date): SlaMetric
    {
        $total = Notification::whereDate('created_at', $date)->count();
        
        $sent = Notification::whereDate('created_at', $date)
            ->where('status_id', DomainNotificationStatus::SENT)
            ->count();

        $actualValue = $total > 0 ? ($sent / $total) * 100 : 100;
        $targetValue = 98; // 98% de sucesso

        return SlaMetric::updateOrCreate(
            [
                'metric_date' => $date->toDateString(),
                'metric_type' => SlaMetric::TYPE_NOTIFICATION_SUCCESS,
                'dimension' => null,
                'dimension_value' => null,
            ],
            [
                'target_value' => $targetValue,
                'actual_value' => round($actualValue, 2),
                'target_met' => $actualValue >= $targetValue,
                'sample_size' => $total,
            ]
        );
    }

    /**
     * Calcula taxa de ocupação.
     */
    protected function calculateOccupancyRate(Carbon $date): SlaMetric
    {
        // Total de horas agendadas vs capacidade
        $schedules = Schedule::onDate($date->toDateString())
            ->whereIn('status_id', [
                DomainScheduleStatus::PLANNED,
                DomainScheduleStatus::IN_PROGRESS,
                DomainScheduleStatus::DONE,
            ])
            ->get();

        $totalHours = 0;
        foreach ($schedules as $schedule) {
            $totalHours += $schedule->duration_hours;
        }

        // Estima capacidade (simplificado: 8h por cuidador ativo)
        $activeCaregivers = $schedules->pluck('caregiver_id')->unique()->count();
        $capacity = max($activeCaregivers * 8, 1);

        $actualValue = ($totalHours / $capacity) * 100;
        $targetValue = ($this->slaTargets['min_occupancy_rate'] ?? 0.60) * 100;

        return SlaMetric::updateOrCreate(
            [
                'metric_date' => $date->toDateString(),
                'metric_type' => SlaMetric::TYPE_OCCUPANCY_RATE,
                'dimension' => null,
                'dimension_value' => null,
            ],
            [
                'target_value' => $targetValue,
                'actual_value' => round(min($actualValue, 100), 2),
                'target_met' => $actualValue >= $targetValue,
                'sample_size' => $activeCaregivers,
            ]
        );
    }

    /**
     * Verifica e cria alertas para métrica fora do SLA.
     */
    protected function checkAndCreateAlerts(SlaMetric $metric): void
    {
        if ($metric->target_met) {
            return;
        }

        $variance = abs($metric->variance_percent);
        $severity = match (true) {
            $variance >= 30 => SlaAlert::SEVERITY_CRITICAL,
            $variance >= 15 => SlaAlert::SEVERITY_WARNING,
            default => SlaAlert::SEVERITY_INFO,
        };

        $typeLabel = SlaMetric::availableTypes()[$metric->metric_type] ?? $metric->metric_type;

        SlaAlert::create([
            'sla_metric_id' => $metric->id,
            'alert_type' => SlaAlert::TYPE_THRESHOLD_BREACH,
            'metric_type' => $metric->metric_type,
            'message' => "Métrica '{$typeLabel}' fora do SLA: {$metric->actual_value}% (meta: {$metric->target_value}%)",
            'severity' => $severity,
        ]);

        Log::warning('Alerta de SLA criado', [
            'metric_type' => $metric->metric_type,
            'actual' => $metric->actual_value,
            'target' => $metric->target_value,
            'severity' => $severity,
        ]);
    }

    /**
     * Obtém alertas não confirmados.
     */
    public function getUnacknowledgedAlerts(): Collection
    {
        return SlaAlert::unacknowledged()
            ->orderByPriority()
            ->with('metric')
            ->get();
    }

    /**
     * Obtém alertas críticos.
     */
    public function getCriticalAlerts(): Collection
    {
        return SlaAlert::unacknowledged()
            ->bySeverity(SlaAlert::SEVERITY_CRITICAL)
            ->with('metric')
            ->get();
    }

    /**
     * Confirma um alerta.
     */
    public function acknowledgeAlert(SlaAlert $alert, int $userId): SlaAlert
    {
        return $alert->acknowledge($userId);
    }

    /**
     * Obtém dashboard de SLA.
     */
    public function getDashboard(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->subDays(7);
        $end = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $metrics = SlaMetric::inPeriod($start->toDateString(), $end->toDateString())
            ->orderBy('metric_date')
            ->get();

        $summary = [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'overall_compliance' => $this->calculateOverallCompliance($metrics),
            'by_metric_type' => $this->groupMetricsByType($metrics),
            'trend' => $this->calculateTrend($metrics),
            'alerts' => [
                'total_unacknowledged' => SlaAlert::unacknowledged()->count(),
                'critical' => SlaAlert::unacknowledged()->bySeverity(SlaAlert::SEVERITY_CRITICAL)->count(),
                'warning' => SlaAlert::unacknowledged()->bySeverity(SlaAlert::SEVERITY_WARNING)->count(),
            ],
        ];

        return $summary;
    }

    /**
     * Calcula compliance geral.
     */
    protected function calculateOverallCompliance(Collection $metrics): float
    {
        if ($metrics->isEmpty()) {
            return 100;
        }

        $totalMet = $metrics->where('target_met', true)->count();
        return round(($totalMet / $metrics->count()) * 100, 2);
    }

    /**
     * Agrupa métricas por tipo.
     */
    protected function groupMetricsByType(Collection $metrics): array
    {
        $grouped = [];
        
        foreach (SlaMetric::availableTypes() as $type => $label) {
            $typeMetrics = $metrics->where('metric_type', $type);
            
            if ($typeMetrics->isNotEmpty()) {
                $grouped[$type] = [
                    'label' => $label,
                    'avg_actual' => round($typeMetrics->avg('actual_value'), 2),
                    'target' => $typeMetrics->first()->target_value,
                    'compliance' => round(($typeMetrics->where('target_met', true)->count() / $typeMetrics->count()) * 100, 2),
                    'sample_count' => $typeMetrics->sum('sample_size'),
                ];
            }
        }

        return $grouped;
    }

    /**
     * Calcula tendência (últimos 7 dias vs anteriores).
     */
    protected function calculateTrend(Collection $metrics): string
    {
        $halfPoint = $metrics->count() / 2;
        
        $firstHalf = $metrics->take((int) $halfPoint);
        $secondHalf = $metrics->skip((int) $halfPoint);

        if ($firstHalf->isEmpty() || $secondHalf->isEmpty()) {
            return 'stable';
        }

        $firstCompliance = $firstHalf->where('target_met', true)->count() / max($firstHalf->count(), 1);
        $secondCompliance = $secondHalf->where('target_met', true)->count() / max($secondHalf->count(), 1);

        if ($secondCompliance > $firstCompliance + 0.05) {
            return 'improving';
        } elseif ($secondCompliance < $firstCompliance - 0.05) {
            return 'declining';
        }

        return 'stable';
    }

    /**
     * Verifica SLA em tempo real e gera alertas proativos.
     */
    public function checkRealTimeSla(): array
    {
        $alerts = [];

        // Verifica atrasos atuais
        $overdueCheckins = Schedule::today()
            ->planned()
            ->get()
            ->filter(function ($schedule) {
                $tolerance = config('operacao.checkin.late_tolerance_minutes', 15);
                return Carbon::now()->diffInMinutes($schedule->start_date_time, false) < -$tolerance;
            });

        if ($overdueCheckins->count() > 0) {
            $alerts[] = [
                'type' => 'realtime_checkin_delay',
                'severity' => $overdueCheckins->count() > 3 ? 'critical' : 'warning',
                'message' => "{$overdueCheckins->count()} agendamento(s) com atraso no check-in",
                'data' => $overdueCheckins->pluck('id')->toArray(),
            ];
        }

        // Verifica emergências não resolvidas
        $pendingEmergencies = Emergency::pending()
            ->get()
            ->filter(function ($emergency) {
                $maxTime = config('operacao.emergency.response_time')[$emergency->severity?->code] ?? 30;
                return Carbon::now()->diffInMinutes($emergency->created_at) > $maxTime;
            });

        if ($pendingEmergencies->count() > 0) {
            $alerts[] = [
                'type' => 'emergency_overdue',
                'severity' => 'critical',
                'message' => "{$pendingEmergencies->count()} emergência(s) pendente(s) acima do tempo de resposta",
                'data' => $pendingEmergencies->pluck('id')->toArray(),
            ];
        }

        return $alerts;
    }
}
