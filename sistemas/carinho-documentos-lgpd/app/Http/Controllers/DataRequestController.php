<?php

namespace App\Http\Controllers;

use App\Models\DataRequest;
use App\Models\DomainConsentSubjectType;
use App\Services\LgpdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataRequestController extends Controller
{
    public function __construct(
        private LgpdService $lgpdService
    ) {}

    /**
     * Lista solicitacoes.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DataRequest::with(['subjectType', 'requestType', 'status']);

        if ($request->has('status')) {
            $query->where('status_id', $request->input('status'));
        }

        if ($request->boolean('pending_only')) {
            $query->pending();
        }

        if ($request->boolean('overdue_only')) {
            $query->overdue();
        }

        $requests = $query->orderBy('requested_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->success($requests);
    }

    /**
     * Cria solicitacao.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_type' => 'required|string|in:client,caregiver',
            'subject_id' => 'required|integer',
            'request_type' => 'required|string|in:export,delete,update',
        ]);

        $subjectTypeId = match ($validated['subject_type']) {
            'client' => DomainConsentSubjectType::CLIENT,
            'caregiver' => DomainConsentSubjectType::CAREGIVER,
            default => DomainConsentSubjectType::CLIENT,
        };

        $dataRequest = match ($validated['request_type']) {
            'export' => $this->lgpdService->requestDataExport($subjectTypeId, $validated['subject_id']),
            'delete' => $this->lgpdService->requestDataDeletion($subjectTypeId, $validated['subject_id']),
            default => null,
        };

        if (!$dataRequest) {
            return $this->error('Falha ao criar solicitacao');
        }

        return $this->created([
            'id' => $dataRequest->id,
            'request_type' => $validated['request_type'],
            'requested_at' => $dataRequest->requested_at->toIso8601String(),
        ]);
    }

    /**
     * Exibe solicitacao.
     */
    public function show(int $id): JsonResponse
    {
        $request = $this->lgpdService->getRequest($id);

        if (!$request) {
            return $this->notFound('Solicitacao nao encontrada');
        }

        return $this->success($request);
    }

    /**
     * Atualiza solicitacao.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:reject',
            'reason' => 'required_if:action,reject|string',
        ]);

        if ($validated['action'] === 'reject') {
            $rejected = $this->lgpdService->rejectRequest($id, $validated['reason']);

            if (!$rejected) {
                return $this->error('Falha ao rejeitar solicitacao');
            }
        }

        return $this->success(null, 'Solicitacao atualizada');
    }

    /**
     * Processa solicitacao.
     */
    public function process(int $id): JsonResponse
    {
        $request = DataRequest::with('requestType')->find($id);

        if (!$request) {
            return $this->notFound('Solicitacao nao encontrada');
        }

        $result = match ($request->requestType->code) {
            'export' => $this->lgpdService->processExportRequest($id),
            'delete' => $this->lgpdService->processDeleteRequest($id),
            default => ['ok' => false, 'error' => 'Tipo de solicitacao invalido'],
        };

        if (!$result['ok']) {
            return $this->error($result['error'] ?? 'Falha ao processar solicitacao');
        }

        return $this->success($result);
    }

    /**
     * Cria solicitacao de exportacao.
     */
    public function requestExport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_type' => 'required|string|in:client,caregiver',
            'subject_id' => 'required|integer',
        ]);

        $subjectTypeId = match ($validated['subject_type']) {
            'client' => DomainConsentSubjectType::CLIENT,
            'caregiver' => DomainConsentSubjectType::CAREGIVER,
            default => DomainConsentSubjectType::CLIENT,
        };

        $dataRequest = $this->lgpdService->requestDataExport($subjectTypeId, $validated['subject_id']);

        if (!$dataRequest) {
            return $this->error('Falha ao criar solicitacao');
        }

        return $this->created([
            'id' => $dataRequest->id,
            'requested_at' => $dataRequest->requested_at->toIso8601String(),
        ]);
    }

    /**
     * Download de exportacao.
     */
    public function downloadExport(int $id): mixed
    {
        $result = $this->lgpdService->processExportRequest($id);

        if (!$result['ok']) {
            return $this->error($result['error'] ?? 'Falha na exportacao');
        }

        return $this->success([
            'download_url' => $result['download_url'],
            'expires_at' => $result['expires_at'],
        ]);
    }

    /**
     * Cria solicitacao de exclusao.
     */
    public function requestDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_type' => 'required|string|in:client,caregiver',
            'subject_id' => 'required|integer',
        ]);

        $subjectTypeId = match ($validated['subject_type']) {
            'client' => DomainConsentSubjectType::CLIENT,
            'caregiver' => DomainConsentSubjectType::CAREGIVER,
            default => DomainConsentSubjectType::CLIENT,
        };

        $dataRequest = $this->lgpdService->requestDataDeletion($subjectTypeId, $validated['subject_id']);

        if (!$dataRequest) {
            return $this->error('Falha ao criar solicitacao');
        }

        return $this->created([
            'id' => $dataRequest->id,
            'requested_at' => $dataRequest->requested_at->toIso8601String(),
        ]);
    }
}
