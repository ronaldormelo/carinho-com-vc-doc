<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\CaregiverAssignment;
use App\Services\WorkloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller para gestão de alocações/serviços dos cuidadores.
 */
class AssignmentController extends Controller
{
    public function __construct(
        private WorkloadService $workloadService
    ) {}

    /**
     * Lista alocações de um cuidador.
     */
    public function index(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador não encontrado', 404);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);

        $query = $caregiver->assignments();

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('start_date')) {
            $query->where('started_at', '>=', $request->get('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('started_at', '<=', $request->get('end_date'));
        }

        $assignments = $query->orderBy('started_at', 'desc')->paginate($perPage);

        return $this->paginated($assignments, 'Alocações carregadas');
    }

    /**
     * Registra nova alocação.
     */
    public function store(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador não encontrado', 404);
        }

        $validator = Validator::make($request->all(), [
            'service_id' => 'required|integer',
            'client_id' => 'nullable|integer',
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date|after:started_at',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados inválidos', 422, $validator->errors()->toArray());
        }

        $result = $this->workloadService->createAssignment(
            $caregiver,
            $request->get('service_id'),
            new \DateTime($request->get('started_at')),
            $request->filled('ended_at') ? new \DateTime($request->get('ended_at')) : null,
            $request->get('client_id'),
            $request->get('notes')
        );

        if (!$result['success']) {
            return $this->error($result['message'], 400, $result);
        }

        return $this->success($result['assignment'], 'Alocação registrada com sucesso', 201);
    }

    /**
     * Exibe alocação específica.
     */
    public function show(int $caregiverId, int $assignmentId): JsonResponse
    {
        $assignment = CaregiverAssignment::where('caregiver_id', $caregiverId)
            ->where('id', $assignmentId)
            ->first();

        if (!$assignment) {
            return $this->error('Alocação não encontrada', 404);
        }

        return $this->success($assignment);
    }

    /**
     * Atualiza alocação.
     */
    public function update(Request $request, int $caregiverId, int $assignmentId): JsonResponse
    {
        $assignment = CaregiverAssignment::where('caregiver_id', $caregiverId)
            ->where('id', $assignmentId)
            ->first();

        if (!$assignment) {
            return $this->error('Alocação não encontrada', 404);
        }

        if ($assignment->status === CaregiverAssignment::STATUS_COMPLETED) {
            return $this->error('Não é possível alterar alocação já concluída', 400);
        }

        $validator = Validator::make($request->all(), [
            'started_at' => 'sometimes|date',
            'ended_at' => 'nullable|date|after:started_at',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados inválidos', 422, $validator->errors()->toArray());
        }

        $assignment->update($validator->validated() + ['updated_at' => now()]);

        return $this->success($assignment->fresh(), 'Alocação atualizada');
    }

    /**
     * Marca alocação como concluída.
     */
    public function complete(Request $request, int $caregiverId, int $assignmentId): JsonResponse
    {
        $assignment = CaregiverAssignment::where('caregiver_id', $caregiverId)
            ->where('id', $assignmentId)
            ->first();

        if (!$assignment) {
            return $this->error('Alocação não encontrada', 404);
        }

        if ($assignment->status === CaregiverAssignment::STATUS_COMPLETED) {
            return $this->error('Alocação já está concluída', 400);
        }

        $hoursWorked = $request->filled('hours_worked') 
            ? (float) $request->get('hours_worked') 
            : null;

        $endedAt = $request->filled('ended_at')
            ? new \DateTime($request->get('ended_at'))
            : null;

        $result = $this->workloadService->completeAssignment($assignment, $hoursWorked, $endedAt);

        return $this->success($result['assignment'], 'Alocação concluída com sucesso');
    }

    /**
     * Cancela alocação.
     */
    public function cancel(Request $request, int $caregiverId, int $assignmentId): JsonResponse
    {
        $assignment = CaregiverAssignment::where('caregiver_id', $caregiverId)
            ->where('id', $assignmentId)
            ->first();

        if (!$assignment) {
            return $this->error('Alocação não encontrada', 404);
        }

        if ($assignment->status === CaregiverAssignment::STATUS_COMPLETED) {
            return $this->error('Não é possível cancelar alocação já concluída', 400);
        }

        $reason = $request->get('reason');
        $assignment->markAsCancelled($reason);

        // Atualiza workload
        $this->workloadService->updateWeeklyWorkload($assignment->caregiver, $assignment->started_at);

        return $this->success($assignment->fresh(), 'Alocação cancelada');
    }

    /**
     * Lista status de alocação disponíveis.
     */
    public function statuses(): JsonResponse
    {
        return $this->success(['statuses' => CaregiverAssignment::STATUSES]);
    }

    /**
     * Histórico resumido de alocações (admin).
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 100);
        $days = min((int) $request->get('days', 30), 90);

        $assignments = CaregiverAssignment::with('caregiver')
            ->recent($days)
            ->orderBy('started_at', 'desc')
            ->paginate($perPage);

        return $this->paginated($assignments, 'Histórico de alocações');
    }
}
