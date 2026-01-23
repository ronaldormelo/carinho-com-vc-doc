<?php

namespace App\Http\Controllers;

use App\Services\ScheduleService;
use App\Models\Schedule;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para gerenciamento de agenda e agendamentos.
 */
class ScheduleController extends Controller
{
    public function __construct(
        protected ScheduleService $scheduleService
    ) {}

    /**
     * Lista agendamentos.
     */
    public function index(Request $request): JsonResponse
    {
        $caregiverId = $request->query('caregiver_id');
        $clientId = $request->query('client_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($caregiverId) {
            $schedules = $this->scheduleService->getCaregiverSchedule(
                (int) $caregiverId,
                $startDate,
                $endDate
            );
        } elseif ($clientId) {
            $schedules = $this->scheduleService->getClientSchedule(
                (int) $clientId,
                $startDate,
                $endDate
            );
        } else {
            $schedules = Schedule::with(['assignment', 'status'])
                ->when($startDate, fn($q) => $q->whereDate('shift_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('shift_date', '<=', $endDate))
                ->orderBy('shift_date')
                ->orderBy('start_time')
                ->paginate(20);

            return $this->success($schedules);
        }

        return $this->success($schedules);
    }

    /**
     * Exibe detalhes de um agendamento.
     */
    public function show(int $id): JsonResponse
    {
        $schedule = Schedule::with([
            'assignment.serviceRequest',
            'status',
            'checkins',
            'serviceLogs',
        ])->find($id);

        if (!$schedule) {
            return $this->notFound('Agendamento nao encontrado.');
        }

        return $this->success($schedule);
    }

    /**
     * Cria agendamentos para uma alocacao.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assignment_id' => 'required|integer|exists:assignments,id',
            'shifts' => 'required|array|min:1',
            'shifts.*.date' => 'required|date',
            'shifts.*.start_time' => 'required|date_format:H:i',
            'shifts.*.end_time' => 'required|date_format:H:i|after:shifts.*.start_time',
        ]);

        $assignment = Assignment::find($validated['assignment_id']);

        if (!$assignment) {
            return $this->notFound('Alocacao nao encontrada.');
        }

        // Valida cada turno
        $errors = [];
        foreach ($validated['shifts'] as $index => $shift) {
            $validationErrors = $this->scheduleService->validateScheduleParams(
                $shift['date'],
                $shift['start_time'],
                $shift['end_time']
            );

            if (!empty($validationErrors)) {
                $errors["shifts.{$index}"] = $validationErrors;
            }

            // Verifica disponibilidade
            if (!$this->scheduleService->isCaregiverAvailable(
                $assignment->caregiver_id,
                $shift['date'],
                $shift['start_time'],
                $shift['end_time']
            )) {
                $errors["shifts.{$index}"][] = 'Cuidador nao disponivel neste horario.';
            }
        }

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        try {
            $schedules = $this->scheduleService->createSchedules($assignment, $validated['shifts']);

            return $this->success($schedules, 'Agendamentos criados com sucesso.', 201);
        } catch (\Throwable $e) {
            return $this->error('Erro ao criar agendamentos: ' . $e->getMessage());
        }
    }

    /**
     * Verifica disponibilidade do cuidador.
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'caregiver_id' => 'required|integer',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $available = $this->scheduleService->isCaregiverAvailable(
            $validated['caregiver_id'],
            $validated['date'],
            $validated['start_time'],
            $validated['end_time']
        );

        return $this->success([
            'available' => $available,
            'caregiver_id' => $validated['caregiver_id'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);
    }

    /**
     * Cancela agendamento.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return $this->notFound('Agendamento nao encontrado.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $result = $this->scheduleService->cancelSchedule($schedule, $validated['reason']);

            return $this->success($result, 'Agendamento cancelado.');
        } catch (\Throwable $e) {
            return $this->error('Erro ao cancelar agendamento: ' . $e->getMessage());
        }
    }

    /**
     * Obtem politica de cancelamento.
     */
    public function cancellationPolicy(int $id): JsonResponse
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return $this->notFound('Agendamento nao encontrado.');
        }

        $policy = $this->scheduleService->getCancellationPolicy($schedule);

        return $this->success($policy);
    }

    /**
     * Obtem agendamentos de hoje.
     */
    public function today(Request $request): JsonResponse
    {
        $caregiverId = $request->query('caregiver_id');

        $schedules = $this->scheduleService->getTodaySchedules(
            $caregiverId ? (int) $caregiverId : null
        );

        return $this->success($schedules);
    }

    /**
     * Obtem proximos agendamentos.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 10);

        $schedules = $this->scheduleService->getUpcomingSchedules((int) $limit);

        return $this->success($schedules);
    }

    /**
     * Obtem estatisticas de ocupacao.
     */
    public function occupancy(Request $request, int $caregiverId): JsonResponse
    {
        $month = $request->query('month');

        $stats = $this->scheduleService->getOccupancyStats($caregiverId, $month);

        return $this->success($stats);
    }
}
