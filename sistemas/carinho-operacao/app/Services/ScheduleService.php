<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Assignment;
use App\Models\ServiceRequest;
use App\Models\DomainScheduleStatus;
use App\Models\DomainAssignmentStatus;
use App\Models\DomainServiceStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service para gerenciamento de agenda e agendamentos.
 */
class ScheduleService
{
    /**
     * Cria agendamentos para uma alocacao.
     */
    public function createSchedules(Assignment $assignment, array $shifts): Collection
    {
        return DB::transaction(function () use ($assignment, $shifts) {
            $schedules = collect();
            $serviceRequest = $assignment->serviceRequest;

            foreach ($shifts as $shift) {
                $schedule = Schedule::create([
                    'assignment_id' => $assignment->id,
                    'caregiver_id' => $assignment->caregiver_id,
                    'client_id' => $serviceRequest->client_id,
                    'shift_date' => $shift['date'],
                    'start_time' => $shift['start_time'],
                    'end_time' => $shift['end_time'],
                    'status_id' => DomainScheduleStatus::PLANNED,
                ]);

                $schedules->push($schedule);
            }

            // Invalida cache de agenda
            $this->invalidateScheduleCache($assignment->caregiver_id);
            $this->invalidateScheduleCache($serviceRequest->client_id, 'client');

            Log::info('Agendamentos criados', [
                'assignment_id' => $assignment->id,
                'count' => $schedules->count(),
            ]);

            return $schedules;
        });
    }

    /**
     * Obtem agenda do cuidador para um periodo.
     */
    public function getCaregiverSchedule(int $caregiverId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::today();
        $end = $endDate ? Carbon::parse($endDate) : $start->copy()->addDays(30);

        $cacheKey = "schedule:caregiver:{$caregiverId}:{$start->format('Y-m-d')}:{$end->format('Y-m-d')}";

        return Cache::remember($cacheKey, 300, function () use ($caregiverId, $start, $end) {
            return Schedule::forCaregiver($caregiverId)
                ->whereBetween('shift_date', [$start, $end])
                ->orderBy('shift_date')
                ->orderBy('start_time')
                ->with(['assignment', 'status'])
                ->get();
        });
    }

    /**
     * Obtem agenda do cliente para um periodo.
     */
    public function getClientSchedule(int $clientId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::today();
        $end = $endDate ? Carbon::parse($endDate) : $start->copy()->addDays(30);

        $cacheKey = "schedule:client:{$clientId}:{$start->format('Y-m-d')}:{$end->format('Y-m-d')}";

        return Cache::remember($cacheKey, 300, function () use ($clientId, $start, $end) {
            return Schedule::forClient($clientId)
                ->whereBetween('shift_date', [$start, $end])
                ->orderBy('shift_date')
                ->orderBy('start_time')
                ->with(['assignment', 'status'])
                ->get();
        });
    }

    /**
     * Obtem agendamentos de hoje.
     */
    public function getTodaySchedules(?int $caregiverId = null): Collection
    {
        $query = Schedule::today()
            ->orderBy('start_time')
            ->with(['assignment.serviceRequest', 'status']);

        if ($caregiverId) {
            $query->forCaregiver($caregiverId);
        }

        return $query->get();
    }

    /**
     * Verifica disponibilidade do cuidador para um horario.
     */
    public function isCaregiverAvailable(int $caregiverId, string $date, string $startTime, string $endTime): bool
    {
        $minGap = config('operacao.scheduling.min_gap_minutes', 60);

        // Converte para timestamps para comparacao
        $requestedStart = Carbon::parse("{$date} {$startTime}");
        $requestedEnd = Carbon::parse("{$date} {$endTime}");

        // Busca agendamentos existentes no dia
        $existingSchedules = Schedule::forCaregiver($caregiverId)
            ->onDate($date)
            ->whereNotIn('status_id', [DomainScheduleStatus::MISSED])
            ->get();

        foreach ($existingSchedules as $schedule) {
            $existingStart = Carbon::parse("{$schedule->shift_date->format('Y-m-d')} {$schedule->start_time}");
            $existingEnd = Carbon::parse("{$schedule->shift_date->format('Y-m-d')} {$schedule->end_time}");

            // Adiciona intervalo minimo
            $existingStart = $existingStart->subMinutes($minGap);
            $existingEnd = $existingEnd->addMinutes($minGap);

            // Verifica sobreposicao
            if ($requestedStart->lt($existingEnd) && $requestedEnd->gt($existingStart)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valida parametros de agendamento.
     */
    public function validateScheduleParams(string $date, string $startTime, string $endTime): array
    {
        $errors = [];
        $config = config('operacao.scheduling');

        $scheduleDate = Carbon::parse($date);
        $now = Carbon::now();

        // Verifica antecedencia minima
        $minAdvance = $config['min_advance_hours'];
        $scheduleStart = Carbon::parse("{$date} {$startTime}");
        if ($scheduleStart->diffInHours($now, false) > -$minAdvance) {
            $errors[] = "Agendamento requer pelo menos {$minAdvance} horas de antecedencia.";
        }

        // Verifica duracao minima e maxima
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $durationHours = $end->diffInMinutes($start) / 60;

        if ($durationHours < $config['min_duration_hours']) {
            $errors[] = "Duracao minima do atendimento e de {$config['min_duration_hours']} horas.";
        }

        if ($durationHours > $config['max_duration_hours']) {
            $errors[] = "Duracao maxima do atendimento e de {$config['max_duration_hours']} horas.";
        }

        return $errors;
    }

    /**
     * Atualiza status do agendamento.
     */
    public function updateStatus(Schedule $schedule, int $statusId): Schedule
    {
        $schedule->status_id = $statusId;
        $schedule->save();

        $this->invalidateScheduleCache($schedule->caregiver_id);
        $this->invalidateScheduleCache($schedule->client_id, 'client');

        Log::info('Status do agendamento atualizado', [
            'schedule_id' => $schedule->id,
            'new_status' => $statusId,
        ]);

        return $schedule;
    }

    /**
     * Inicia um agendamento (muda status para em andamento).
     */
    public function startSchedule(Schedule $schedule): Schedule
    {
        if (!$schedule->isPlanned()) {
            throw new \InvalidArgumentException('Agendamento nao esta planejado.');
        }

        return $this->updateStatus($schedule, DomainScheduleStatus::IN_PROGRESS);
    }

    /**
     * Finaliza um agendamento.
     */
    public function completeSchedule(Schedule $schedule): Schedule
    {
        if (!$schedule->isInProgress()) {
            throw new \InvalidArgumentException('Agendamento nao esta em andamento.');
        }

        return $this->updateStatus($schedule, DomainScheduleStatus::DONE);
    }

    /**
     * Marca agendamento como perdido/faltou.
     */
    public function markAsMissed(Schedule $schedule): Schedule
    {
        return $this->updateStatus($schedule, DomainScheduleStatus::MISSED);
    }

    /**
     * Cancela um agendamento.
     */
    public function cancelSchedule(Schedule $schedule, string $reason): array
    {
        return DB::transaction(function () use ($schedule, $reason) {
            // Verifica politica de cancelamento
            $policy = $this->getCancellationPolicy($schedule);

            // Atualiza o agendamento
            $schedule->status_id = DomainScheduleStatus::MISSED;
            $schedule->save();

            // Invalida cache
            $this->invalidateScheduleCache($schedule->caregiver_id);
            $this->invalidateScheduleCache($schedule->client_id, 'client');

            Log::info('Agendamento cancelado', [
                'schedule_id' => $schedule->id,
                'reason' => $reason,
                'fee_percent' => $policy['fee_percent'],
            ]);

            return [
                'schedule' => $schedule,
                'policy' => $policy,
            ];
        });
    }

    /**
     * Calcula politica de cancelamento.
     */
    public function getCancellationPolicy(Schedule $schedule): array
    {
        $config = config('operacao.cancellation');
        $hoursUntilService = Carbon::now()->diffInHours($schedule->start_date_time, false);

        if ($hoursUntilService >= $config['free_cancellation_hours']) {
            return [
                'type' => 'free',
                'fee_percent' => 0,
                'message' => 'Cancelamento gratuito.',
            ];
        }

        if ($hoursUntilService >= $config['reduced_fee_hours']) {
            return [
                'type' => 'reduced',
                'fee_percent' => $config['reduced_fee_percent'],
                'message' => "Taxa de cancelamento reduzida: {$config['reduced_fee_percent']}%.",
            ];
        }

        return [
            'type' => 'full',
            'fee_percent' => $config['full_fee_percent'],
            'message' => "Taxa de cancelamento integral: {$config['full_fee_percent']}%.",
        ];
    }

    /**
     * Obtem proximos agendamentos.
     */
    public function getUpcomingSchedules(int $limit = 10): Collection
    {
        return Schedule::future()
            ->planned()
            ->orderBy('shift_date')
            ->orderBy('start_time')
            ->limit($limit)
            ->with(['assignment.serviceRequest', 'status'])
            ->get();
    }

    /**
     * Obtem agendamentos que precisam de lembrete.
     */
    public function getSchedulesNeedingReminder(int $hoursAhead): Collection
    {
        $targetTime = Carbon::now()->addHours($hoursAhead);

        return Schedule::planned()
            ->whereDate('shift_date', $targetTime->toDateString())
            ->whereTime('start_time', '<=', $targetTime->addMinutes(30)->format('H:i:s'))
            ->whereTime('start_time', '>', $targetTime->subMinutes(30)->format('H:i:s'))
            ->with(['assignment.serviceRequest'])
            ->get();
    }

    /**
     * Invalida cache de agenda.
     */
    protected function invalidateScheduleCache(int $id, string $type = 'caregiver'): void
    {
        $pattern = "schedule:{$type}:{$id}:*";
        // Em producao, usar Redis::keys() e Cache::forget()
        Cache::forget($pattern);
    }

    /**
     * Obtem estatisticas de ocupacao.
     */
    public function getOccupancyStats(int $caregiverId, ?string $month = null): array
    {
        $targetMonth = $month ? Carbon::parse($month) : Carbon::now();
        $startOfMonth = $targetMonth->copy()->startOfMonth();
        $endOfMonth = $targetMonth->copy()->endOfMonth();

        $schedules = Schedule::forCaregiver($caregiverId)
            ->whereBetween('shift_date', [$startOfMonth, $endOfMonth])
            ->get();

        $totalHours = 0;
        $completedHours = 0;

        foreach ($schedules as $schedule) {
            $hours = $schedule->duration_hours;
            $totalHours += $hours;

            if ($schedule->isDone()) {
                $completedHours += $hours;
            }
        }

        $daysInMonth = $targetMonth->daysInMonth;
        $workHoursPerDay = 8;
        $maxPossibleHours = $daysInMonth * $workHoursPerDay;

        return [
            'total_schedules' => $schedules->count(),
            'completed_schedules' => $schedules->where('status_id', DomainScheduleStatus::DONE)->count(),
            'missed_schedules' => $schedules->where('status_id', DomainScheduleStatus::MISSED)->count(),
            'total_hours' => round($totalHours, 1),
            'completed_hours' => round($completedHours, 1),
            'occupancy_rate' => $maxPossibleHours > 0 ? round(($totalHours / $maxPossibleHours) * 100, 1) : 0,
        ];
    }
}
