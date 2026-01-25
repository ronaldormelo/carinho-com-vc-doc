<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Assignment;
use App\Models\Substitution;
use App\Models\Emergency;
use App\Models\Notification;
use App\Models\Checkin;
use App\Models\ServiceRequest;
use App\Models\DomainScheduleStatus;
use App\Models\DomainAssignmentStatus;
use App\Models\DomainServiceStatus;
use App\Models\DomainNotificationStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service para relatórios operacionais consolidados.
 * 
 * Fornece visão gerencial completa da operação para tomada
 * de decisão baseada em dados conforme práticas de gestão.
 */
class ReportService
{
    /**
     * Gera relatório operacional diário.
     */
    public function getDailyReport(?string $date = null): array
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::today();

        return [
            'date' => $targetDate->toDateString(),
            'schedules' => $this->getSchedulesSummary($targetDate),
            'assignments' => $this->getAssignmentsSummary($targetDate),
            'checkins' => $this->getCheckinsSummary($targetDate),
            'substitutions' => $this->getSubstitutionsSummary($targetDate),
            'emergencies' => $this->getEmergenciesSummary($targetDate),
            'notifications' => $this->getNotificationsSummary($targetDate),
        ];
    }

    /**
     * Gera relatório operacional semanal.
     */
    public function getWeeklyReport(?string $weekStart = null): array
    {
        $start = $weekStart ? Carbon::parse($weekStart)->startOfWeek() : Carbon::now()->startOfWeek();
        $end = $start->copy()->endOfWeek();

        $dailyReports = [];
        $current = $start->copy();

        while ($current <= $end) {
            $dailyReports[$current->toDateString()] = $this->getDailyReport($current->toDateString());
            $current->addDay();
        }

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'summary' => $this->aggregateReports($dailyReports),
            'daily_breakdown' => $dailyReports,
            'trends' => $this->calculateWeeklyTrends($dailyReports),
        ];
    }

    /**
     * Gera relatório operacional mensal.
     */
    public function getMonthlyReport(?string $month = null): array
    {
        $targetMonth = $month ? Carbon::parse($month)->startOfMonth() : Carbon::now()->startOfMonth();
        $start = $targetMonth->copy();
        $end = $targetMonth->copy()->endOfMonth();

        return [
            'period' => [
                'month' => $targetMonth->format('Y-m'),
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'schedules' => $this->getSchedulesPeriodReport($start, $end),
            'assignments' => $this->getAssignmentsPeriodReport($start, $end),
            'substitutions' => $this->getSubstitutionsPeriodReport($start, $end),
            'emergencies' => $this->getEmergenciesPeriodReport($start, $end),
            'caregivers' => $this->getCaregiverPerformanceReport($start, $end),
            'regions' => $this->getRegionPerformanceReport($start, $end),
        ];
    }

    /**
     * Resumo de agendamentos do dia.
     */
    protected function getSchedulesSummary(Carbon $date): array
    {
        $schedules = Schedule::onDate($date->toDateString())->get();

        return [
            'total' => $schedules->count(),
            'planned' => $schedules->where('status_id', DomainScheduleStatus::PLANNED)->count(),
            'in_progress' => $schedules->where('status_id', DomainScheduleStatus::IN_PROGRESS)->count(),
            'done' => $schedules->where('status_id', DomainScheduleStatus::DONE)->count(),
            'missed' => $schedules->where('status_id', DomainScheduleStatus::MISSED)->count(),
            'total_hours' => round($schedules->sum('duration_hours'), 1),
            'completed_hours' => round(
                $schedules->where('status_id', DomainScheduleStatus::DONE)->sum('duration_hours'),
                1
            ),
        ];
    }

    /**
     * Resumo de alocações do dia.
     */
    protected function getAssignmentsSummary(Carbon $date): array
    {
        $assignments = Assignment::whereDate('assigned_at', $date)->get();

        return [
            'total' => $assignments->count(),
            'assigned' => $assignments->where('status_id', DomainAssignmentStatus::ASSIGNED)->count(),
            'confirmed' => $assignments->where('status_id', DomainAssignmentStatus::CONFIRMED)->count(),
            'replaced' => $assignments->where('status_id', DomainAssignmentStatus::REPLACED)->count(),
            'canceled' => $assignments->where('status_id', DomainAssignmentStatus::CANCELED)->count(),
        ];
    }

    /**
     * Resumo de check-ins do dia.
     */
    protected function getCheckinsSummary(Carbon $date): array
    {
        $checkins = Checkin::whereDate('timestamp', $date)->get();
        $tolerance = config('operacao.checkin.late_tolerance_minutes', 15);

        $lateCount = 0;
        foreach ($checkins->where('check_type_id', 1) as $checkin) {
            if ($checkin->isLate()) {
                $lateCount++;
            }
        }

        $totalCheckins = $checkins->where('check_type_id', 1)->count();

        return [
            'total_checkins' => $totalCheckins,
            'total_checkouts' => $checkins->where('check_type_id', 2)->count(),
            'on_time' => $totalCheckins - $lateCount,
            'late' => $lateCount,
            'punctuality_rate' => $totalCheckins > 0 
                ? round((($totalCheckins - $lateCount) / $totalCheckins) * 100, 2) 
                : 100,
        ];
    }

    /**
     * Resumo de substituições do dia.
     */
    protected function getSubstitutionsSummary(Carbon $date): array
    {
        $substitutions = Substitution::whereDate('created_at', $date)->get();

        $byReason = $substitutions->groupBy('reason')
            ->map(fn($group) => $group->count())
            ->toArray();

        return [
            'total' => $substitutions->count(),
            'by_reason' => $byReason,
        ];
    }

    /**
     * Resumo de emergências do dia.
     */
    protected function getEmergenciesSummary(Carbon $date): array
    {
        $emergencies = Emergency::whereDate('created_at', $date)->get();

        $resolved = $emergencies->whereNotNull('resolved_at');
        $avgResolutionTime = 0;

        if ($resolved->count() > 0) {
            $totalTime = 0;
            foreach ($resolved as $e) {
                $totalTime += $e->resolved_at->diffInMinutes($e->created_at);
            }
            $avgResolutionTime = round($totalTime / $resolved->count());
        }

        return [
            'total' => $emergencies->count(),
            'resolved' => $resolved->count(),
            'pending' => $emergencies->whereNull('resolved_at')->count(),
            'by_severity' => [
                'low' => $emergencies->where('severity_id', 1)->count(),
                'medium' => $emergencies->where('severity_id', 2)->count(),
                'high' => $emergencies->where('severity_id', 3)->count(),
                'critical' => $emergencies->where('severity_id', 4)->count(),
            ],
            'avg_resolution_time_minutes' => $avgResolutionTime,
        ];
    }

    /**
     * Resumo de notificações do dia.
     */
    protected function getNotificationsSummary(Carbon $date): array
    {
        $notifications = Notification::whereDate('created_at', $date)->get();

        return [
            'total' => $notifications->count(),
            'queued' => $notifications->where('status_id', DomainNotificationStatus::QUEUED)->count(),
            'sent' => $notifications->where('status_id', DomainNotificationStatus::SENT)->count(),
            'failed' => $notifications->where('status_id', DomainNotificationStatus::FAILED)->count(),
            'success_rate' => $notifications->count() > 0
                ? round(($notifications->where('status_id', DomainNotificationStatus::SENT)->count() / $notifications->count()) * 100, 2)
                : 100,
        ];
    }

    /**
     * Relatório de agendamentos por período.
     */
    protected function getSchedulesPeriodReport(Carbon $start, Carbon $end): array
    {
        $schedules = Schedule::whereBetween('shift_date', [$start, $end])->get();

        return [
            'total' => $schedules->count(),
            'by_status' => [
                'planned' => $schedules->where('status_id', DomainScheduleStatus::PLANNED)->count(),
                'in_progress' => $schedules->where('status_id', DomainScheduleStatus::IN_PROGRESS)->count(),
                'done' => $schedules->where('status_id', DomainScheduleStatus::DONE)->count(),
                'missed' => $schedules->where('status_id', DomainScheduleStatus::MISSED)->count(),
            ],
            'total_hours' => round($schedules->sum('duration_hours'), 1),
            'completed_hours' => round(
                $schedules->where('status_id', DomainScheduleStatus::DONE)->sum('duration_hours'),
                1
            ),
            'completion_rate' => $schedules->count() > 0
                ? round(($schedules->where('status_id', DomainScheduleStatus::DONE)->count() / $schedules->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Relatório de alocações por período.
     */
    protected function getAssignmentsPeriodReport(Carbon $start, Carbon $end): array
    {
        $assignments = Assignment::whereBetween('assigned_at', [$start, $end])->get();

        return [
            'total' => $assignments->count(),
            'by_status' => [
                'assigned' => $assignments->where('status_id', DomainAssignmentStatus::ASSIGNED)->count(),
                'confirmed' => $assignments->where('status_id', DomainAssignmentStatus::CONFIRMED)->count(),
                'replaced' => $assignments->where('status_id', DomainAssignmentStatus::REPLACED)->count(),
                'canceled' => $assignments->where('status_id', DomainAssignmentStatus::CANCELED)->count(),
            ],
            'confirmation_rate' => $assignments->count() > 0
                ? round(($assignments->where('status_id', DomainAssignmentStatus::CONFIRMED)->count() / $assignments->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Relatório de substituições por período.
     */
    protected function getSubstitutionsPeriodReport(Carbon $start, Carbon $end): array
    {
        $substitutions = Substitution::whereBetween('created_at', [$start, $end])->get();

        $byReason = $substitutions->groupBy('reason')
            ->map(fn($group) => $group->count())
            ->sortDesc()
            ->toArray();

        $totalAssignments = Assignment::whereBetween('assigned_at', [$start, $end])->count();

        return [
            'total' => $substitutions->count(),
            'by_reason' => $byReason,
            'rate' => $totalAssignments > 0
                ? round(($substitutions->count() / $totalAssignments) * 100, 2)
                : 0,
        ];
    }

    /**
     * Relatório de emergências por período.
     */
    protected function getEmergenciesPeriodReport(Carbon $start, Carbon $end): array
    {
        $emergencies = Emergency::whereBetween('created_at', [$start, $end])->get();

        $resolved = $emergencies->whereNotNull('resolved_at');
        $avgResolutionTime = 0;

        if ($resolved->count() > 0) {
            $totalTime = 0;
            foreach ($resolved as $e) {
                $totalTime += $e->resolved_at->diffInMinutes($e->created_at);
            }
            $avgResolutionTime = round($totalTime / $resolved->count());
        }

        return [
            'total' => $emergencies->count(),
            'resolved' => $resolved->count(),
            'pending' => $emergencies->whereNull('resolved_at')->count(),
            'resolution_rate' => $emergencies->count() > 0
                ? round(($resolved->count() / $emergencies->count()) * 100, 2)
                : 100,
            'avg_resolution_time_minutes' => $avgResolutionTime,
            'by_severity' => [
                'low' => $emergencies->where('severity_id', 1)->count(),
                'medium' => $emergencies->where('severity_id', 2)->count(),
                'high' => $emergencies->where('severity_id', 3)->count(),
                'critical' => $emergencies->where('severity_id', 4)->count(),
            ],
        ];
    }

    /**
     * Relatório de performance de cuidadores.
     */
    protected function getCaregiverPerformanceReport(Carbon $start, Carbon $end): array
    {
        $schedules = Schedule::whereBetween('shift_date', [$start, $end])
            ->where('status_id', DomainScheduleStatus::DONE)
            ->get();

        $byCaregiverId = $schedules->groupBy('caregiver_id');

        $performance = [];
        foreach ($byCaregiverId as $caregiverId => $caregiverSchedules) {
            $performance[] = [
                'caregiver_id' => $caregiverId,
                'total_schedules' => $caregiverSchedules->count(),
                'total_hours' => round($caregiverSchedules->sum('duration_hours'), 1),
            ];
        }

        // Ordena por horas trabalhadas
        usort($performance, fn($a, $b) => $b['total_hours'] <=> $a['total_hours']);

        return [
            'total_caregivers' => count($performance),
            'top_performers' => array_slice($performance, 0, 10),
        ];
    }

    /**
     * Relatório de performance por região (simplificado).
     */
    protected function getRegionPerformanceReport(Carbon $start, Carbon $end): array
    {
        // Simplificado - em produção buscaria região do cliente via CRM
        return [
            'note' => 'Relatório por região requer integração com dados de endereço do CRM',
        ];
    }

    /**
     * Agrega relatórios diários em resumo semanal.
     */
    protected function aggregateReports(array $dailyReports): array
    {
        $totalSchedules = 0;
        $totalDone = 0;
        $totalSubstitutions = 0;
        $totalEmergencies = 0;
        $totalHours = 0;

        foreach ($dailyReports as $report) {
            $totalSchedules += $report['schedules']['total'] ?? 0;
            $totalDone += $report['schedules']['done'] ?? 0;
            $totalSubstitutions += $report['substitutions']['total'] ?? 0;
            $totalEmergencies += $report['emergencies']['total'] ?? 0;
            $totalHours += $report['schedules']['completed_hours'] ?? 0;
        }

        return [
            'total_schedules' => $totalSchedules,
            'total_completed' => $totalDone,
            'completion_rate' => $totalSchedules > 0 ? round(($totalDone / $totalSchedules) * 100, 2) : 0,
            'total_substitutions' => $totalSubstitutions,
            'substitution_rate' => $totalSchedules > 0 ? round(($totalSubstitutions / $totalSchedules) * 100, 2) : 0,
            'total_emergencies' => $totalEmergencies,
            'total_hours' => round($totalHours, 1),
        ];
    }

    /**
     * Calcula tendências semanais.
     */
    protected function calculateWeeklyTrends(array $dailyReports): array
    {
        $completionRates = [];
        
        foreach ($dailyReports as $date => $report) {
            $total = $report['schedules']['total'] ?? 0;
            $done = $report['schedules']['done'] ?? 0;
            $completionRates[$date] = $total > 0 ? round(($done / $total) * 100, 2) : 100;
        }

        $values = array_values($completionRates);
        $trend = 'stable';

        if (count($values) >= 3) {
            $firstHalf = array_slice($values, 0, (int)(count($values) / 2));
            $secondHalf = array_slice($values, (int)(count($values) / 2));
            
            $avgFirst = array_sum($firstHalf) / count($firstHalf);
            $avgSecond = array_sum($secondHalf) / count($secondHalf);

            if ($avgSecond > $avgFirst + 5) {
                $trend = 'improving';
            } elseif ($avgSecond < $avgFirst - 5) {
                $trend = 'declining';
            }
        }

        return [
            'completion_rate_by_day' => $completionRates,
            'trend' => $trend,
        ];
    }

    /**
     * Gera relatório de exceções operacionais.
     */
    public function getExceptionsReport(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->subMonth();
        $end = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $exceptions = DB::table('operational_exceptions')
            ->whereBetween('requested_at', [$start, $end])
            ->get();

        $byType = $exceptions->groupBy('exception_type')
            ->map(fn($group) => $group->count())
            ->toArray();

        $byStatus = $exceptions->groupBy('status')
            ->map(fn($group) => $group->count())
            ->toArray();

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'total' => $exceptions->count(),
            'by_type' => $byType,
            'by_status' => $byStatus,
            'approval_rate' => $exceptions->count() > 0
                ? round(($exceptions->where('status', 'approved')->count() / $exceptions->count()) * 100, 2)
                : 0,
        ];
    }
}
