<?php

namespace App\Http\Controllers;

use App\Services\ServiceRequestService;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para gerenciamento de solicitacoes de servico.
 */
class ServiceRequestController extends Controller
{
    public function __construct(
        protected ServiceRequestService $serviceRequestService
    ) {}

    /**
     * Lista solicitacoes de servico.
     */
    public function index(Request $request): JsonResponse
    {
        $clientId = $request->query('client_id');
        $statusId = $request->query('status_id');

        if ($clientId) {
            $requests = $this->serviceRequestService->getClientRequests((int) $clientId, $statusId);
        } else {
            $requests = ServiceRequest::with(['serviceType', 'urgency', 'status'])
                ->when($statusId, fn($q) => $q->where('status_id', $statusId))
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return $this->success($requests);
        }

        return $this->success($requests);
    }

    /**
     * Exibe detalhes de uma solicitacao.
     */
    public function show(int $id): JsonResponse
    {
        $request = $this->serviceRequestService->getRequestDetails($id);

        if (!$request) {
            return $this->notFound('Solicitacao nao encontrada.');
        }

        return $this->success($request);
    }

    /**
     * Cria nova solicitacao.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|integer',
            'service_type_id' => 'required|integer|in:1,2,3',
            'urgency_id' => 'required|integer|in:1,2,3',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $serviceRequest = $this->serviceRequestService->createServiceRequest($validated);

            return $this->success($serviceRequest, 'Solicitacao criada com sucesso.', 201);
        } catch (\Throwable $e) {
            return $this->error('Erro ao criar solicitacao: ' . $e->getMessage());
        }
    }

    /**
     * Inicia processamento (alocacao de cuidador).
     */
    public function process(Request $request, int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::find($id);

        if (!$serviceRequest) {
            return $this->notFound('Solicitacao nao encontrada.');
        }

        $requirements = $request->only(['skills', 'region']);

        try {
            $result = $this->serviceRequestService->startProcessing($serviceRequest, $requirements);

            return $this->success($result, $result['message']);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            return $this->error('Erro ao processar solicitacao: ' . $e->getMessage());
        }
    }

    /**
     * Ativa solicitacao.
     */
    public function activate(int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::find($id);

        if (!$serviceRequest) {
            return $this->notFound('Solicitacao nao encontrada.');
        }

        try {
            $serviceRequest = $this->serviceRequestService->activate($serviceRequest);

            return $this->success($serviceRequest, 'Solicitacao ativada.');
        } catch (\Throwable $e) {
            return $this->error('Erro ao ativar solicitacao: ' . $e->getMessage());
        }
    }

    /**
     * Completa solicitacao.
     */
    public function complete(int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::find($id);

        if (!$serviceRequest) {
            return $this->notFound('Solicitacao nao encontrada.');
        }

        try {
            $serviceRequest = $this->serviceRequestService->complete($serviceRequest);

            return $this->success($serviceRequest, 'Solicitacao concluida.');
        } catch (\Throwable $e) {
            return $this->error('Erro ao concluir solicitacao: ' . $e->getMessage());
        }
    }

    /**
     * Cancela solicitacao.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::find($id);

        if (!$serviceRequest) {
            return $this->notFound('Solicitacao nao encontrada.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $serviceRequest = $this->serviceRequestService->cancel($serviceRequest, $validated['reason']);

            return $this->success($serviceRequest, 'Solicitacao cancelada.');
        } catch (\Throwable $e) {
            return $this->error('Erro ao cancelar solicitacao: ' . $e->getMessage());
        }
    }

    /**
     * Obtem solicitacoes abertas.
     */
    public function open(): JsonResponse
    {
        $requests = $this->serviceRequestService->getOpenRequests();

        return $this->success($requests);
    }

    /**
     * Obtem solicitacoes urgentes.
     */
    public function urgent(): JsonResponse
    {
        $requests = $this->serviceRequestService->getUrgentRequests();

        return $this->success($requests);
    }

    /**
     * Importa solicitacao do atendimento.
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'atendimento_id' => 'required|integer',
        ]);

        try {
            $serviceRequest = $this->serviceRequestService->importFromAtendimento($validated['atendimento_id']);

            return $this->success($serviceRequest, 'Solicitacao importada com sucesso.', 201);
        } catch (\Throwable $e) {
            return $this->error('Erro ao importar solicitacao: ' . $e->getMessage());
        }
    }

    /**
     * Obtem estatisticas de solicitacoes.
     */
    public function stats(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $stats = $this->serviceRequestService->getRequestStats($startDate, $endDate);

        return $this->success($stats);
    }
}
