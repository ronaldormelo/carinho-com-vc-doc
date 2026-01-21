<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(
        protected ClientService $clientService
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
}
