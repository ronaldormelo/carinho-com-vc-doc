<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\Domain\DomainEventType;
use App\Services\ClientEventService;
use App\Services\ClientService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(
        protected ClientService $clientService,
        protected ClientEventService $clientEventService
    ) {}

    /**
     * Lista todos os clientes com filtros e paginação
     */
    public function index(Request $request)
    {
        $query = Client::with(['lead', 'careNeeds.patientType']);

        // Filtros
        if ($request->has('city')) {
            $query->byCity($request->city);
        }

        if ($request->has('with_active_contracts') && $request->with_active_contracts) {
            $query->withActiveContracts();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('primary_contact', 'LIKE', "%{$search}%")
                  ->orWhere('city', 'LIKE', "%{$search}%")
                  ->orWhereHas('lead', function ($leadQuery) use ($search) {
                      $leadQuery->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Ordenação
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginação
        $perPage = min($request->get('per_page', 15), 100);
        $clients = $query->paginate($perPage);

        return ClientResource::collection($clients);
    }

    /**
     * Cria um novo cliente
     */
    public function store(ClientRequest $request)
    {
        $client = $this->clientService->createClient($request->validated());

        return $this->createdResponse(
            new ClientResource($client->load(['lead', 'careNeeds.patientType'])),
            'Cliente criado com sucesso'
        );
    }

    /**
     * Exibe um cliente específico
     */
    public function show(Client $client)
    {
        $client->load([
            'lead.urgency',
            'lead.serviceType',
            'lead.status',
            'careNeeds.patientType',
            'contracts.status',
            'contracts.proposal.serviceType',
            'consents',
        ]);

        return new ClientResource($client);
    }

    /**
     * Atualiza um cliente
     */
    public function update(ClientRequest $request, Client $client)
    {
        $client = $this->clientService->updateClient($client, $request->validated());

        return $this->successResponse(
            new ClientResource($client->load(['lead', 'careNeeds.patientType'])),
            'Cliente atualizado com sucesso'
        );
    }

    /**
     * Remove um cliente
     */
    public function destroy(Client $client)
    {
        // Verificar se tem contratos ativos
        if ($client->hasActiveContract()) {
            return $this->errorResponse(
                'Não é possível excluir um cliente com contrato ativo',
                422
            );
        }

        $client->delete();

        return $this->successResponse(null, 'Cliente excluído com sucesso');
    }

    /**
     * Adiciona necessidade de cuidado
     */
    public function addCareNeed(Request $request, Client $client)
    {
        $request->validate([
            'patient_type_id' => 'required|exists:domain_patient_type,id',
            'conditions_json' => 'nullable|array',
            'notes' => 'nullable|string|max:2000',
        ]);

        $careNeed = $this->clientService->addCareNeed($client, $request->all());

        return $this->createdResponse($careNeed, 'Necessidade de cuidado adicionada');
    }

    /**
     * Adiciona consentimento LGPD
     */
    public function addConsent(Request $request, Client $client)
    {
        $request->validate([
            'consent_type' => 'required|string|max:64',
            'source' => 'required|string|max:64',
        ]);

        $consent = $this->clientService->addConsent($client, $request->all());

        return $this->createdResponse($consent, 'Consentimento registrado');
    }

    /**
     * Lista consentimentos do cliente
     */
    public function consents(Client $client)
    {
        return $this->successResponse($client->consents);
    }

    /**
     * Obtém histórico completo do cliente
     */
    public function history(Client $client)
    {
        $history = $this->clientService->getClientHistory($client);

        return $this->successResponse($history);
    }

    /**
     * Timeline de eventos do cliente.
     */
    public function events(Client $client)
    {
        $events = $this->clientEventService->getTimeline($client->id);

        return $this->successResponse($events);
    }

    /**
     * Registra evento na timeline (Operação / hub).
     */
    public function logEvent(Request $request, Client $client)
    {
        $validated = $request->validate([
            'event_type' => 'nullable|string|max:64',
            'event_type_id' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'data' => 'nullable|array',
            'source' => 'nullable|string|max:64',
            'timestamp' => 'nullable|date',
        ]);

        $typeId = (int) ($validated['event_type_id'] ?? $this->resolveEventTypeId($validated['event_type'] ?? ''));
        $title = $validated['title'] ?? ($validated['event_type'] ?: 'Evento operacional');
        $occurredAt = isset($validated['timestamp'])
            ? new \DateTime((string) $validated['timestamp'])
            : null;

        $event = $this->clientEventService->logEvent(
            $client->id,
            $typeId,
            $title,
            $validated['description'] ?? ($validated['source'] ?? null),
            $validated['data'] ?? null,
            null,
            null,
            $occurredAt
        );

        return $this->createdResponse($event, 'Evento registrado');
    }

    private function resolveEventTypeId(string $eventType): int
    {
        $normalized = strtolower($eventType);

        return match (true) {
            str_contains($normalized, 'payment') && str_contains($normalized, 'overdue') => DomainEventType::PAYMENT_OVERDUE,
            str_contains($normalized, 'payment') => DomainEventType::PAYMENT_RECEIVED,
            str_contains($normalized, 'contract') && str_contains($normalized, 'sign') => DomainEventType::CONTRACT_SIGNED,
            str_contains($normalized, 'contract') => DomainEventType::CONTRACT_ACTIVATED,
            str_contains($normalized, 'service') => DomainEventType::REVIEW_COMPLETED,
            default => DomainEventType::CONTACT_PHONE,
        };
    }
}
