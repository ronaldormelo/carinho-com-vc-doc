<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\DomainServiceStatus;
use App\Models\DomainServiceType;
use App\Models\DomainChecklistType;
use App\Integrations\Crm\CrmClient;
use App\Integrations\Atendimento\AtendimentoClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para gerenciamento de solicitacoes de servico.
 */
class ServiceRequestService
{
    public function __construct(
        protected CrmClient $crmClient,
        protected AtendimentoClient $atendimentoClient,
        protected MatchService $matchService,
        protected CheckinService $checkinService
    ) {}

    /**
     * Cria uma nova solicitacao de servico.
     */
    public function createServiceRequest(array $data): ServiceRequest
    {
        return DB::transaction(function () use ($data) {
            $serviceRequest = ServiceRequest::create([
                'client_id' => $data['client_id'],
                'service_type_id' => $data['service_type_id'],
                'urgency_id' => $data['urgency_id'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status_id' => DomainServiceStatus::OPEN,
            ]);

            // Cria checklists padrao
            $this->createDefaultChecklists($serviceRequest);

            // Sincroniza com CRM
            $this->syncWithCrm($serviceRequest);

            Log::info('Solicitacao de servico criada', [
                'service_request_id' => $serviceRequest->id,
                'client_id' => $data['client_id'],
            ]);

            return $serviceRequest;
        });
    }

    /**
     * Cria checklists padrao para a solicitacao.
     */
    protected function createDefaultChecklists(ServiceRequest $serviceRequest): void
    {
        $templates = $this->checkinService->getDefaultChecklistTemplates();

        // Checklist de inicio
        $this->checkinService->createChecklist(
            $serviceRequest->id,
            DomainChecklistType::START,
            $templates['start']
        );

        // Checklist de fim
        $this->checkinService->createChecklist(
            $serviceRequest->id,
            DomainChecklistType::END,
            $templates['end']
        );
    }

    /**
     * Sincroniza solicitacao com CRM.
     */
    protected function syncWithCrm(ServiceRequest $serviceRequest): void
    {
        $this->crmClient->syncServiceRequest([
            'source' => 'operacao',
            'service_request_id' => $serviceRequest->id,
            'client_id' => $serviceRequest->client_id,
            'service_type' => $serviceRequest->service_type_id,
            'status' => $serviceRequest->status_id,
            'created_at' => $serviceRequest->created_at->toIso8601String(),
        ]);
    }

    /**
     * Atualiza status da solicitacao.
     */
    public function updateStatus(ServiceRequest $serviceRequest, int $statusId): ServiceRequest
    {
        $oldStatus = $serviceRequest->status_id;
        $serviceRequest->status_id = $statusId;
        $serviceRequest->save();

        // Sincroniza mudanca de status com CRM
        $this->crmClient->updateServiceRequestStatus($serviceRequest->id, $statusId);

        Log::info('Status da solicitacao atualizado', [
            'service_request_id' => $serviceRequest->id,
            'old_status' => $oldStatus,
            'new_status' => $statusId,
        ]);

        return $serviceRequest;
    }

    /**
     * Inicia processamento da solicitacao (agenda cuidador).
     */
    public function startProcessing(ServiceRequest $serviceRequest, array $requirements = []): array
    {
        if (!$serviceRequest->isOpen()) {
            throw new \InvalidArgumentException('Solicitacao nao esta aberta para processamento.');
        }

        // Tenta match automatico
        $assignment = $this->matchService->autoMatch($serviceRequest, $requirements);

        if ($assignment) {
            $this->updateStatus($serviceRequest, DomainServiceStatus::SCHEDULED);

            return [
                'success' => true,
                'message' => 'Cuidador alocado automaticamente.',
                'assignment' => $assignment,
            ];
        }

        // Se nao conseguiu match automatico, retorna candidatos
        $candidates = $this->matchService->findCandidates($serviceRequest, $requirements);

        return [
            'success' => false,
            'message' => 'Match automatico nao disponivel. Selecione manualmente.',
            'candidates' => $candidates,
        ];
    }

    /**
     * Ativa a solicitacao (servico em andamento).
     */
    public function activate(ServiceRequest $serviceRequest): ServiceRequest
    {
        return $this->updateStatus($serviceRequest, DomainServiceStatus::ACTIVE);
    }

    /**
     * Completa a solicitacao.
     */
    public function complete(ServiceRequest $serviceRequest): ServiceRequest
    {
        return $this->updateStatus($serviceRequest, DomainServiceStatus::COMPLETED);
    }

    /**
     * Cancela a solicitacao.
     */
    public function cancel(ServiceRequest $serviceRequest, string $reason): ServiceRequest
    {
        // Cancela todas as alocacoes ativas
        $serviceRequest->assignments()
            ->active()
            ->update(['status_id' => \App\Models\DomainAssignmentStatus::CANCELED]);

        $serviceRequest = $this->updateStatus($serviceRequest, DomainServiceStatus::CANCELED);

        Log::info('Solicitacao cancelada', [
            'service_request_id' => $serviceRequest->id,
            'reason' => $reason,
        ]);

        return $serviceRequest;
    }

    /**
     * Obtem solicitacoes abertas.
     */
    public function getOpenRequests(int $limit = 50): Collection
    {
        return ServiceRequest::open()
            ->orderBy('urgency_id') // Urgentes primeiro
            ->orderBy('created_at')
            ->limit($limit)
            ->with(['serviceType', 'urgency', 'status'])
            ->get();
    }

    /**
     * Obtem solicitacoes de um cliente.
     */
    public function getClientRequests(int $clientId, ?int $statusId = null): Collection
    {
        $query = ServiceRequest::forClient($clientId)
            ->orderBy('created_at', 'desc')
            ->with(['serviceType', 'urgency', 'status', 'activeAssignment']);

        if ($statusId) {
            $query->where('status_id', $statusId);
        }

        return $query->get();
    }

    /**
     * Obtem solicitacoes urgentes.
     */
    public function getUrgentRequests(): Collection
    {
        return ServiceRequest::open()
            ->urgent()
            ->orderBy('created_at')
            ->with(['serviceType', 'urgency'])
            ->get();
    }

    /**
     * Obtem detalhes completos da solicitacao.
     */
    public function getRequestDetails(int $serviceRequestId): ?ServiceRequest
    {
        return ServiceRequest::with([
            'serviceType',
            'urgency',
            'status',
            'assignments' => fn($q) => $q->orderBy('assigned_at', 'desc'),
            'assignments.status',
            'assignments.schedules',
            'checklists.entries',
            'emergencies' => fn($q) => $q->orderBy('id', 'desc'),
        ])->find($serviceRequestId);
    }

    /**
     * Importa solicitacao do sistema de atendimento.
     */
    public function importFromAtendimento(int $atendimentoId): ServiceRequest
    {
        $response = $this->atendimentoClient->getDemanda($atendimentoId);

        if (!$response['ok']) {
            throw new \RuntimeException('Erro ao buscar demanda do atendimento: ' . ($response['error'] ?? 'Unknown'));
        }

        $demanda = $response['body']['demanda'] ?? null;
        if (!$demanda) {
            throw new \RuntimeException('Demanda nao encontrada.');
        }

        return $this->createServiceRequest([
            'client_id' => $demanda['client_id'],
            'service_type_id' => $this->mapServiceType($demanda['tipo']),
            'urgency_id' => $this->mapUrgency($demanda['urgencia']),
            'start_date' => $demanda['data_inicio'] ?? null,
            'end_date' => $demanda['data_fim'] ?? null,
        ]);
    }

    /**
     * Mapeia tipo de servico do atendimento.
     */
    protected function mapServiceType(string $tipo): int
    {
        return match ($tipo) {
            'horista' => DomainServiceType::HORISTA,
            'diario' => DomainServiceType::DIARIO,
            'mensal' => DomainServiceType::MENSAL,
            default => DomainServiceType::DIARIO,
        };
    }

    /**
     * Mapeia urgencia do atendimento.
     */
    protected function mapUrgency(string $urgencia): int
    {
        return match ($urgencia) {
            'hoje' => \App\Models\DomainUrgencyLevel::HOJE,
            'semana' => \App\Models\DomainUrgencyLevel::SEMANA,
            default => \App\Models\DomainUrgencyLevel::SEM_DATA,
        };
    }

    /**
     * Obtem estatisticas de solicitacoes.
     */
    public function getRequestStats(?string $startDate = null, ?string $endDate = null): array
    {
        $query = ServiceRequest::query();

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $requests = $query->get();

        return [
            'total' => $requests->count(),
            'open' => $requests->where('status_id', DomainServiceStatus::OPEN)->count(),
            'scheduled' => $requests->where('status_id', DomainServiceStatus::SCHEDULED)->count(),
            'active' => $requests->where('status_id', DomainServiceStatus::ACTIVE)->count(),
            'completed' => $requests->where('status_id', DomainServiceStatus::COMPLETED)->count(),
            'canceled' => $requests->where('status_id', DomainServiceStatus::CANCELED)->count(),
            'by_type' => [
                'horista' => $requests->where('service_type_id', DomainServiceType::HORISTA)->count(),
                'diario' => $requests->where('service_type_id', DomainServiceType::DIARIO)->count(),
                'mensal' => $requests->where('service_type_id', DomainServiceType::MENSAL)->count(),
            ],
        ];
    }
}
