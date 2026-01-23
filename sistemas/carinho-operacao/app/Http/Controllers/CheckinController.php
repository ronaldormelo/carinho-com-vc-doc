<?php

namespace App\Http\Controllers;

use App\Services\CheckinService;
use App\Services\ScheduleService;
use App\Models\Schedule;
use App\Models\Checklist;
use App\Models\ChecklistEntry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para gerenciamento de check-in/out e checklists.
 */
class CheckinController extends Controller
{
    public function __construct(
        protected CheckinService $checkinService,
        protected ScheduleService $scheduleService
    ) {}

    /**
     * Realiza check-in.
     */
    public function checkin(Request $request, int $scheduleId): JsonResponse
    {
        $schedule = Schedule::find($scheduleId);

        if (!$schedule) {
            return $this->notFound('Agendamento nao encontrado.');
        }

        $validated = $request->validate([
            'location' => 'nullable|string|max:255',
        ]);

        try {
            $checkin = $this->checkinService->performCheckin($schedule, $validated['location'] ?? null);

            return $this->success([
                'checkin' => $checkin,
                'schedule' => $schedule->fresh(['status']),
                'is_late' => $checkin->isLate(),
                'delay_minutes' => $checkin->delay_minutes,
            ], 'Check-in realizado com sucesso.');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            return $this->error('Erro ao realizar check-in: ' . $e->getMessage());
        }
    }

    /**
     * Realiza check-out.
     */
    public function checkout(Request $request, int $scheduleId): JsonResponse
    {
        $schedule = Schedule::find($scheduleId);

        if (!$schedule) {
            return $this->notFound('Agendamento nao encontrado.');
        }

        $validated = $request->validate([
            'location' => 'nullable|string|max:255',
            'activities' => 'nullable|array',
            'activities.*' => 'string|max:500',
        ]);

        try {
            $checkout = $this->checkinService->performCheckout(
                $schedule,
                $validated['location'] ?? null,
                $validated['activities'] ?? null
            );

            return $this->success([
                'checkout' => $checkout,
                'schedule' => $schedule->fresh(['status']),
            ], 'Check-out realizado com sucesso.');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            return $this->error('Erro ao realizar check-out: ' . $e->getMessage());
        }
    }

    /**
     * Obtem checklist de inicio.
     */
    public function startChecklist(int $serviceRequestId): JsonResponse
    {
        $checklist = $this->checkinService->getStartChecklist($serviceRequestId);

        if (!$checklist) {
            return $this->notFound('Checklist de inicio nao encontrado.');
        }

        return $this->success($checklist);
    }

    /**
     * Obtem checklist de fim.
     */
    public function endChecklist(int $serviceRequestId): JsonResponse
    {
        $checklist = $this->checkinService->getEndChecklist($serviceRequestId);

        if (!$checklist) {
            return $this->notFound('Checklist de fim nao encontrado.');
        }

        return $this->success($checklist);
    }

    /**
     * Atualiza item do checklist.
     */
    public function updateChecklistItem(Request $request, int $entryId): JsonResponse
    {
        $entry = ChecklistEntry::find($entryId);

        if (!$entry) {
            return $this->notFound('Item de checklist nao encontrado.');
        }

        $validated = $request->validate([
            'completed' => 'required|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $entry = $this->checkinService->updateChecklistItem(
                $entryId,
                $validated['completed'],
                $validated['notes'] ?? null
            );

            return $this->success($entry, 'Item atualizado.');
        } catch (\Throwable $e) {
            return $this->error('Erro ao atualizar item: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza multiplos itens do checklist.
     */
    public function updateChecklistBatch(Request $request, int $checklistId): JsonResponse
    {
        $checklist = Checklist::find($checklistId);

        if (!$checklist) {
            return $this->notFound('Checklist nao encontrado.');
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.entry_id' => 'required|integer|exists:checklist_entries,id',
            'items.*.completed' => 'required|boolean',
            'items.*.notes' => 'nullable|string|max:1000',
        ]);

        try {
            $updatedItems = collect();

            foreach ($validated['items'] as $item) {
                $entry = $this->checkinService->updateChecklistItem(
                    $item['entry_id'],
                    $item['completed'],
                    $item['notes'] ?? null
                );
                $updatedItems->push($entry);
            }

            return $this->success([
                'updated_items' => $updatedItems,
                'checklist' => $checklist->fresh(['entries']),
                'completion_percent' => $checklist->completion_percent,
            ], 'Itens atualizados.');
        } catch (\Throwable $e) {
            return $this->error('Erro ao atualizar itens: ' . $e->getMessage());
        }
    }

    /**
     * Registra atividades realizadas.
     */
    public function logActivities(Request $request, int $scheduleId): JsonResponse
    {
        $schedule = Schedule::find($scheduleId);

        if (!$schedule) {
            return $this->notFound('Agendamento nao encontrado.');
        }

        $validated = $request->validate([
            'activities' => 'required|array|min:1',
            'activities.*' => 'string|max:500',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $log = $this->checkinService->logActivities(
                $schedule,
                $validated['activities'],
                $validated['notes'] ?? null
            );

            return $this->success($log, 'Atividades registradas.', 201);
        } catch (\Throwable $e) {
            return $this->error('Erro ao registrar atividades: ' . $e->getMessage());
        }
    }

    /**
     * Obtem logs de servico de um agendamento.
     */
    public function serviceLogs(int $scheduleId): JsonResponse
    {
        $schedule = Schedule::find($scheduleId);

        if (!$schedule) {
            return $this->notFound('Agendamento nao encontrado.');
        }

        $logs = $this->checkinService->getServiceLogs($scheduleId);

        return $this->success($logs);
    }

    /**
     * Verifica atrasos e retorna alertas.
     */
    public function checkDelays(): JsonResponse
    {
        $delays = $this->checkinService->checkDelays();

        return $this->success([
            'delays' => $delays,
            'count' => $delays->count(),
        ]);
    }

    /**
     * Obtem templates de checklist padrao.
     */
    public function checklistTemplates(): JsonResponse
    {
        $templates = $this->checkinService->getDefaultChecklistTemplates();

        return $this->success($templates);
    }

    /**
     * Valida localizacao de check-in/out.
     */
    public function validateLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location' => 'required|string',
            'expected_lat' => 'required|numeric',
            'expected_lng' => 'required|numeric',
        ]);

        $isValid = $this->checkinService->validateLocation(
            $validated['location'],
            [
                'lat' => $validated['expected_lat'],
                'lng' => $validated['expected_lng'],
            ]
        );

        return $this->success([
            'valid' => $isValid,
            'max_distance_meters' => config('operacao.checkin.max_distance_meters'),
        ]);
    }
}
