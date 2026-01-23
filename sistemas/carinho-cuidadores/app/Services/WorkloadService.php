<?php

namespace App\Services;

use App\Models\Caregiver;
use App\Models\CaregiverAssignment;
use App\Models\CaregiverWorkload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para gerenciamento de carga de trabalho dos cuidadores.
 * Implementa controles consolidados de horas trabalhadas e limites operacionais.
 */
class WorkloadService
{
    /**
     * Registra uma nova alocação de serviço.
     */
    public function createAssignment(
        Caregiver $caregiver,
        int $serviceId,
        \DateTimeInterface $startedAt,
        ?\DateTimeInterface $endedAt = null,
        ?int $clientId = null,
        ?string $notes = null
    ): array {
        // Verifica se cuidador está ativo
        if (!$caregiver->is_active) {
            return [
                'success' => false,
                'message' => 'Cuidador não está ativo',
            ];
        }

        // Verifica se está afastado na data
        if (!$caregiver->isAvailableOn($startedAt)) {
            return [
                'success' => false,
                'message' => 'Cuidador não está disponível nesta data',
            ];
        }

        // Calcula duração estimada
        $estimatedHours = 0;
        if ($endedAt) {
            $estimatedHours = $startedAt->diff($endedAt)->h + ($startedAt->diff($endedAt)->i / 60);
        }

        // Verifica limite de horas semanais
        if (!$caregiver->canWorkMoreHours($estimatedHours)) {
            $maxHours = config('cuidadores.operacional.max_weekly_hours', 44);
            $currentHours = $caregiver->current_week_hours;
            
            return [
                'success' => false,
                'message' => "Limite de horas semanais atingido. Atual: {$currentHours}h, Máximo: {$maxHours}h",
                'current_hours' => $currentHours,
                'max_hours' => $maxHours,
            ];
        }

        $assignment = CaregiverAssignment::create([
            'caregiver_id' => $caregiver->id,
            'service_id' => $serviceId,
            'client_id' => $clientId,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'status' => CaregiverAssignment::STATUS_SCHEDULED,
            'notes' => $notes,
            'created_at' => now(),
        ]);

        // Atualiza workload da semana
        $this->updateWeeklyWorkload($caregiver, $startedAt);

        Log::info('Alocação criada', [
            'assignment_id' => $assignment->id,
            'caregiver_id' => $caregiver->id,
            'service_id' => $serviceId,
        ]);

        return [
            'success' => true,
            'assignment' => $assignment,
        ];
    }

    /**
     * Conclui uma alocação e registra horas trabalhadas.
     */
    public function completeAssignment(
        CaregiverAssignment $assignment,
        ?float $hoursWorked = null,
        ?\DateTimeInterface $endedAt = null
    ): array {
        $endedAt = $endedAt ?? now();
        $hoursWorked = $hoursWorked ?? $assignment->calculateHoursWorked();

        $assignment->update([
            'status' => CaregiverAssignment::STATUS_COMPLETED,
            'ended_at' => $endedAt,
            'hours_worked' => $hoursWorked,
            'updated_at' => now(),
        ]);

        // Atualiza workload da semana
        $this->updateWeeklyWorkload($assignment->caregiver, $assignment->started_at);

        Log::info('Alocação concluída', [
            'assignment_id' => $assignment->id,
            'hours_worked' => $hoursWorked,
        ]);

        return [
            'success' => true,
            'assignment' => $assignment->fresh(),
        ];
    }

    /**
     * Atualiza o workload semanal de um cuidador.
     */
    public function updateWeeklyWorkload(Caregiver $caregiver, ?\DateTimeInterface $weekStart = null): CaregiverWorkload
    {
        $weekStart = $weekStart ? (clone $weekStart)->startOfWeek() : now()->startOfWeek();
        
        $workload = CaregiverWorkload::getOrCreateForWeek($caregiver->id, $weekStart);
        $workload->recalculate();

        return $workload;
    }

    /**
     * Retorna cuidadores com sobrecarga na semana atual.
     */
    public function getOverloadedCaregivers(): array
    {
        $maxHours = config('cuidadores.operacional.max_weekly_hours', 44);
        $alertHours = config('cuidadores.operacional.overtime_alert_hours', 40);

        $workloads = CaregiverWorkload::currentWeek()
            ->where('hours_worked', '>=', $alertHours)
            ->with('caregiver')
            ->orderByDesc('hours_worked')
            ->get();

        return $workloads->map(fn ($w) => [
            'caregiver_id' => $w->caregiver_id,
            'caregiver_name' => $w->caregiver?->name,
            'hours_worked' => (float) $w->hours_worked,
            'hours_overtime' => (float) $w->hours_overtime,
            'is_overloaded' => $w->is_overloaded,
            'utilization_rate' => $w->utilization_rate,
        ])->toArray();
    }

    /**
     * Retorna cuidadores disponíveis com capacidade para mais horas.
     */
    public function getAvailableCaregivers(
        float $requiredHours,
        ?string $city = null,
        ?string $careType = null,
        ?int $dayOfWeek = null
    ): array {
        $maxHours = config('cuidadores.operacional.max_weekly_hours', 44);
        $weekStart = now()->startOfWeek();

        $query = Caregiver::query()
            ->active()
            ->with(['skills.careType', 'regions']);

        // Filtro por cidade
        if ($city) {
            $query->where(function ($q) use ($city) {
                $q->where('city', $city)
                    ->orWhereHas('regions', fn ($q2) => $q2->where('city', $city));
            });
        }

        // Filtro por tipo de cuidado
        if ($careType) {
            $query->whereHas('skills', function ($q) use ($careType) {
                $q->whereHas('careType', fn ($q2) => $q2->where('code', $careType));
            });
        }

        // Filtro por disponibilidade no dia
        if ($dayOfWeek !== null) {
            $query->whereHas('availability', fn ($q) => $q->where('day_of_week', $dayOfWeek));
        }

        // Exclui afastados
        $query->whereDoesntHave('leaves', function ($q) {
            $q->where('approved', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now());
        });

        $caregivers = $query->get();

        // Filtra por capacidade de horas
        $available = [];
        foreach ($caregivers as $caregiver) {
            $workload = CaregiverWorkload::where('caregiver_id', $caregiver->id)
                ->where('week_start', $weekStart->format('Y-m-d'))
                ->first();

            $currentHours = $workload ? (float) $workload->hours_worked : 0;
            $availableHours = $maxHours - $currentHours;

            if ($availableHours >= $requiredHours) {
                $available[] = [
                    'id' => $caregiver->id,
                    'name' => $caregiver->name,
                    'phone' => $caregiver->phone,
                    'city' => $caregiver->city,
                    'experience_years' => $caregiver->experience_years,
                    'average_rating' => $caregiver->average_rating,
                    'current_hours' => $currentHours,
                    'available_hours' => $availableHours,
                    'skills' => $caregiver->skills->map(fn ($s) => $s->careType?->code)->toArray(),
                ];
            }
        }

        // Ordena por horas disponíveis (mais disponível primeiro)
        usort($available, fn ($a, $b) => $b['available_hours'] <=> $a['available_hours']);

        return $available;
    }

    /**
     * Retorna resumo de carga de trabalho de um cuidador.
     */
    public function getWorkloadSummary(Caregiver $caregiver, int $weeks = 4): array
    {
        $summaries = [];
        $startDate = now()->startOfWeek();

        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = (clone $startDate)->subWeeks($i);
            $workload = CaregiverWorkload::where('caregiver_id', $caregiver->id)
                ->where('week_start', $weekStart->format('Y-m-d'))
                ->first();

            $summaries[] = [
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => (clone $weekStart)->addDays(6)->format('Y-m-d'),
                'hours_scheduled' => $workload ? (float) $workload->hours_scheduled : 0,
                'hours_worked' => $workload ? (float) $workload->hours_worked : 0,
                'hours_overtime' => $workload ? (float) $workload->hours_overtime : 0,
                'assignments_count' => $workload ? $workload->assignments_count : 0,
                'utilization_rate' => $workload ? $workload->utilization_rate : 0,
            ];
        }

        $maxHours = config('cuidadores.operacional.max_weekly_hours', 44);
        $totalHours = array_sum(array_column($summaries, 'hours_worked'));
        $avgHours = $weeks > 0 ? $totalHours / $weeks : 0;

        return [
            'caregiver_id' => $caregiver->id,
            'caregiver_name' => $caregiver->name,
            'max_weekly_hours' => $maxHours,
            'current_week' => $summaries[0] ?? null,
            'weekly_history' => $summaries,
            'average_weekly_hours' => round($avgHours, 2),
            'total_hours_period' => round($totalHours, 2),
        ];
    }
}
