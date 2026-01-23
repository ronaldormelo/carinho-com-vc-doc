<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\CaregiverLeave;
use App\Models\DomainLeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller para gestão de afastamentos (atestados, férias, licenças).
 */
class LeaveController extends Controller
{
    /**
     * Lista afastamentos de um cuidador.
     */
    public function index(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador não encontrado', 404);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);

        $query = $caregiver->leaves()->with('leaveType');

        // Filtros
        if ($request->filled('status')) {
            match ($request->get('status')) {
                'approved' => $query->approved(),
                'pending' => $query->pending(),
                'current' => $query->approved()->current(),
                'upcoming' => $query->approved()->upcoming(),
                default => null,
            };
        }

        if ($request->filled('type')) {
            $query->ofType($request->get('type'));
        }

        $leaves = $query->orderBy('start_date', 'desc')->paginate($perPage);

        return $this->paginated($leaves, 'Afastamentos carregados');
    }

    /**
     * Registra novo afastamento.
     */
    public function store(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador não encontrado', 404);
        }

        $validator = Validator::make($request->all(), [
            'leave_type_id' => 'required|integer|exists:domain_leave_type,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:2000',
            'document_url' => 'nullable|string|max:512',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados inválidos', 422, $validator->errors()->toArray());
        }

        // Verifica conflito com afastamentos existentes
        $conflict = $caregiver->leaves()
            ->approved()
            ->forPeriod($request->get('start_date'), $request->get('end_date'))
            ->exists();

        if ($conflict) {
            return $this->error('Já existe um afastamento aprovado para este período', 400);
        }

        $leave = CaregiverLeave::create([
            'caregiver_id' => $caregiver->id,
            'leave_type_id' => $request->get('leave_type_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'reason' => $request->get('reason'),
            'document_url' => $request->get('document_url'),
            'approved' => false,
            'created_at' => now(),
        ]);

        return $this->success(
            $leave->load('leaveType'),
            'Afastamento registrado com sucesso',
            201
        );
    }

    /**
     * Exibe afastamento específico.
     */
    public function show(int $caregiverId, int $leaveId): JsonResponse
    {
        $leave = CaregiverLeave::where('caregiver_id', $caregiverId)
            ->where('id', $leaveId)
            ->with('leaveType')
            ->first();

        if (!$leave) {
            return $this->error('Afastamento não encontrado', 404);
        }

        return $this->success($leave);
    }

    /**
     * Atualiza afastamento.
     */
    public function update(Request $request, int $caregiverId, int $leaveId): JsonResponse
    {
        $leave = CaregiverLeave::where('caregiver_id', $caregiverId)
            ->where('id', $leaveId)
            ->first();

        if (!$leave) {
            return $this->error('Afastamento não encontrado', 404);
        }

        if ($leave->approved) {
            return $this->error('Não é possível alterar afastamento já aprovado', 400);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:2000',
            'document_url' => 'nullable|string|max:512',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados inválidos', 422, $validator->errors()->toArray());
        }

        $leave->update($validator->validated() + ['updated_at' => now()]);

        return $this->success($leave->fresh('leaveType'), 'Afastamento atualizado');
    }

    /**
     * Aprova afastamento.
     */
    public function approve(Request $request, int $caregiverId, int $leaveId): JsonResponse
    {
        $leave = CaregiverLeave::where('caregiver_id', $caregiverId)
            ->where('id', $leaveId)
            ->first();

        if (!$leave) {
            return $this->error('Afastamento não encontrado', 404);
        }

        if ($leave->approved) {
            return $this->error('Afastamento já está aprovado', 400);
        }

        $approvedBy = $request->get('approved_by', 'Sistema');
        $leave->approve($approvedBy);

        return $this->success($leave->fresh('leaveType'), 'Afastamento aprovado');
    }

    /**
     * Rejeita afastamento.
     */
    public function reject(Request $request, int $caregiverId, int $leaveId): JsonResponse
    {
        $leave = CaregiverLeave::where('caregiver_id', $caregiverId)
            ->where('id', $leaveId)
            ->first();

        if (!$leave) {
            return $this->error('Afastamento não encontrado', 404);
        }

        $reason = $request->get('reason');
        $rejectedBy = $request->get('rejected_by', 'Sistema');
        $leave->reject($rejectedBy, $reason);

        return $this->success($leave->fresh('leaveType'), 'Afastamento rejeitado');
    }

    /**
     * Remove afastamento.
     */
    public function destroy(int $caregiverId, int $leaveId): JsonResponse
    {
        $leave = CaregiverLeave::where('caregiver_id', $caregiverId)
            ->where('id', $leaveId)
            ->first();

        if (!$leave) {
            return $this->error('Afastamento não encontrado', 404);
        }

        if ($leave->approved && $leave->start_date <= now()) {
            return $this->error('Não é possível remover afastamento aprovado em andamento', 400);
        }

        $leave->delete();

        return $this->success(null, 'Afastamento removido');
    }

    /**
     * Lista tipos de afastamento disponíveis.
     */
    public function types(): JsonResponse
    {
        $types = DomainLeaveType::all()->map(fn ($t) => [
            'id' => $t->id,
            'code' => $t->code,
            'label' => $t->label,
        ]);

        return $this->success(['types' => $types]);
    }

    /**
     * Lista todos os afastamentos pendentes de aprovação (admin).
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 100);

        $leaves = CaregiverLeave::pending()
            ->with(['caregiver', 'leaveType'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginated($leaves, 'Afastamentos pendentes');
    }

    /**
     * Lista afastamentos ativos no momento (admin).
     */
    public function current(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 100);

        $leaves = CaregiverLeave::approved()
            ->current()
            ->with(['caregiver', 'leaveType'])
            ->orderBy('end_date')
            ->paginate($perPage);

        return $this->paginated($leaves, 'Afastamentos ativos');
    }
}
