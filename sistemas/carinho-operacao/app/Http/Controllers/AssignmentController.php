<?php

namespace App\Http\Controllers;

use App\Services\MatchService;
use App\Services\SubstitutionService;
use App\Models\Assignment;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para gerenciamento de alocacoes de cuidadores.
 */
class AssignmentController extends Controller
{
    public function __construct(
        protected MatchService $matchService,
        protected SubstitutionService $substitutionService
    ) {}

    /**
     * Lista alocacoes.
     */
    public function index(Request $request): JsonResponse
    {
        $serviceRequestId = $request->query('service_request_id');
        $caregiverId = $request->query('caregiver_id');

        $query = Assignment::with(['serviceRequest', 'status', 'schedules'])
            ->when($serviceRequestId, fn($q) => $q->where('service_request_id', $serviceRequestId))
            ->when($caregiverId, fn($q) => $q->where('caregiver_id', $caregiverId))
            ->orderBy('assigned_at', 'desc');

        $assignments = $query->paginate(20);

        return $this->success($assignments);
    }

    /**
     * Exibe detalhes de uma alocacao.
     */
    public function show(int $id): JsonResponse
    {
        $assignment = Assignment::with([
            'serviceRequest.serviceType',
            'status',
            'schedules.status',
            'schedules.checkins',
            'substitutions',
        ])->find($id);

        if (!$assignment) {
            return $this->notFound('Alocacao nao encontrada.');
        }

        return $this->success($assignment);
    }

    /**
     * Busca candidatos para uma solicitacao.
     */
    public function findCandidates(Request $request, int $serviceRequestId): JsonResponse
    {
        $serviceRequest = ServiceRequest::find($serviceRequestId);

        if (!$serviceRequest) {
            return $this->notFound('Solicitacao nao encontrada.');
        }

        $requirements = $request->only(['skills', 'region']);

        try {
            $candidates = $this->matchService->findCandidates($serviceRequest, $requirements);

            return $this->success([
                'candidates' => $candidates,
                'count' => $candidates->count(),
            ]);
        } catch (\Throwable $e) {
            return $this->error('Erro ao buscar candidatos: ' . $e->getMessage());
        }
    }

    /**
     * Aloca cuidador para uma solicitacao.
     */
    public function assign(Request $request, int $serviceRequestId): JsonResponse
    {
        $serviceRequest = ServiceRequest::find($serviceRequestId);

        if (!$serviceRequest) {
            return $this->notFound('Solicitacao nao encontrada.');
        }

        $validated = $request->validate([
            'caregiver_id' => 'required|integer',
        ]);

        try {
            $assignment = $this->matchService->assignCaregiver($serviceRequest, $validated['caregiver_id']);

            return $this->success($assignment, 'Cuidador alocado com sucesso.', 201);
        } catch (\Throwable $e) {
            return $this->error('Erro ao alocar cuidador: ' . $e->getMessage());
        }
    }

    /**
     * Confirma alocacao.
     */
    public function confirm(int $id): JsonResponse
    {
        $assignment = Assignment::find($id);

        if (!$assignment) {
            return $this->notFound('Alocacao nao encontrada.');
        }

        try {
            $assignment = $this->matchService->confirmAssignment($assignment);

            return $this->success($assignment, 'Alocacao confirmada.');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            return $this->error('Erro ao confirmar alocacao: ' . $e->getMessage());
        }
    }

    /**
     * Verifica compatibilidade cliente-cuidador.
     */
    public function checkCompatibility(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|integer',
            'caregiver_id' => 'required|integer',
        ]);

        $compatibility = $this->matchService->checkCompatibility(
            $validated['client_id'],
            $validated['caregiver_id']
        );

        return $this->success($compatibility);
    }

    /**
     * Processa substituicao.
     */
    public function substitute(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::find($id);

        if (!$assignment) {
            return $this->notFound('Alocacao nao encontrada.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'new_caregiver_id' => 'nullable|integer',
        ]);

        try {
            $result = $this->substitutionService->processSubstitution(
                $assignment,
                $validated['reason'],
                $validated['new_caregiver_id'] ?? null
            );

            if ($result['success']) {
                return $this->success($result, $result['message']);
            }

            return $this->error($result['message']);
        } catch (\Throwable $e) {
            return $this->error('Erro ao processar substituicao: ' . $e->getMessage());
        }
    }

    /**
     * Busca substitutos urgentes.
     */
    public function findSubstitutes(int $id): JsonResponse
    {
        $assignment = Assignment::find($id);

        if (!$assignment) {
            return $this->notFound('Alocacao nao encontrada.');
        }

        try {
            $substitutes = $this->substitutionService->findUrgentSubstitutes($assignment);

            return $this->success([
                'candidates' => $substitutes,
                'count' => $substitutes->count(),
            ]);
        } catch (\Throwable $e) {
            return $this->error('Erro ao buscar substitutos: ' . $e->getMessage());
        }
    }

    /**
     * Obtem historico de substituicoes.
     */
    public function substitutionHistory(int $id): JsonResponse
    {
        $history = $this->substitutionService->getSubstitutionHistory($id);

        return $this->success($history);
    }

    /**
     * Obtem estatisticas de substituicao de um cuidador.
     */
    public function caregiverSubstitutionStats(int $caregiverId): JsonResponse
    {
        $stats = $this->substitutionService->getCaregiverSubstitutionStats($caregiverId);

        return $this->success($stats);
    }
}
