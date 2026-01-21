<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadRequest;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use App\Models\Domain\DomainLeadStatus;
use App\Services\LeadService;
use App\Events\LeadCreated;
use App\Events\LeadStatusChanged;
use App\Events\LeadLost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService
    ) {}

    /**
     * Lista todos os leads com filtros e paginação
     */
    public function index(Request $request)
    {
        $query = Lead::with(['urgency', 'serviceType', 'status'])
            ->withCount('interactions');

        // Filtros
        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->has('city')) {
            $query->byCity($request->city);
        }

        if ($request->has('source')) {
            $query->bySource($request->source);
        }

        if ($request->has('urgency_id')) {
            $query->where('urgency_id', $request->urgency_id);
        }

        if ($request->has('service_type_id')) {
            $query->where('service_type_id', $request->service_type_id);
        }

        if ($request->has('in_pipeline') && $request->in_pipeline) {
            $query->inPipeline();
        }

        if ($request->has('urgent') && $request->urgent) {
            $query->urgent();
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->createdBetween($request->start_date, $request->end_date);
        }

        // Ordenação
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginação
        $perPage = min($request->get('per_page', 15), 100);
        $leads = $query->paginate($perPage);

        return LeadResource::collection($leads);
    }

    /**
     * Cria um novo lead
     */
    public function store(LeadRequest $request)
    {
        $lead = $this->leadService->createLead($request->validated());

        event(new LeadCreated($lead));

        return $this->createdResponse(
            new LeadResource($lead->load(['urgency', 'serviceType', 'status'])),
            'Lead criado com sucesso'
        );
    }

    /**
     * Exibe um lead específico
     */
    public function show(Lead $lead)
    {
        $lead->load([
            'urgency',
            'serviceType',
            'status',
            'client.careNeeds.patientType',
            'deals.stage',
            'deals.status',
            'tasks.status',
            'interactions.channel',
            'lossReason',
        ]);

        return new LeadResource($lead);
    }

    /**
     * Atualiza um lead
     */
    public function update(LeadRequest $request, Lead $lead)
    {
        $oldStatusId = $lead->status_id;

        $lead = $this->leadService->updateLead($lead, $request->validated());

        // Verificar se o status mudou
        if ($request->has('status_id') && $oldStatusId !== $lead->status_id) {
            event(new LeadStatusChanged($lead, $oldStatusId));

            // Se perdeu, disparar evento específico
            if ($lead->status_id === DomainLeadStatus::LOST) {
                event(new LeadLost($lead));
            }
        }

        return $this->successResponse(
            new LeadResource($lead->load(['urgency', 'serviceType', 'status'])),
            'Lead atualizado com sucesso'
        );
    }

    /**
     * Remove um lead (soft delete não implementado - cuidado!)
     */
    public function destroy(Lead $lead)
    {
        // Verificar se pode deletar (não tem cliente vinculado)
        if ($lead->client()->exists()) {
            return $this->errorResponse(
                'Não é possível excluir um lead que já foi convertido em cliente',
                422
            );
        }

        $lead->delete();

        return $this->successResponse(null, 'Lead excluído com sucesso');
    }

    /**
     * Avança o lead no pipeline
     */
    public function advanceStatus(Request $request, Lead $lead)
    {
        $request->validate([
            'status_id' => 'required|exists:domain_lead_status,id',
        ]);

        $newStatusId = $request->status_id;

        if (!$lead->canAdvanceTo($newStatusId)) {
            return $this->errorResponse(
                'Transição de status não permitida',
                422
            );
        }

        $oldStatusId = $lead->status_id;
        $lead->status_id = $newStatusId;
        $lead->save();

        event(new LeadStatusChanged($lead, $oldStatusId));

        return $this->successResponse(
            new LeadResource($lead->load(['urgency', 'serviceType', 'status'])),
            'Status do lead atualizado com sucesso'
        );
    }

    /**
     * Marca um lead como perdido
     */
    public function markAsLost(Request $request, Lead $lead)
    {
        $request->validate([
            'reason' => 'required|string|max:128',
            'details' => 'nullable|string|max:2000',
        ]);

        if ($lead->isLost()) {
            return $this->errorResponse('Lead já está marcado como perdido', 422);
        }

        $lead = $this->leadService->markAsLost(
            $lead,
            $request->reason,
            $request->details
        );

        event(new LeadLost($lead));

        return $this->successResponse(
            new LeadResource($lead->load(['urgency', 'serviceType', 'status', 'lossReason'])),
            'Lead marcado como perdido'
        );
    }

    /**
     * Busca leads por telefone ou nome
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:3',
        ]);

        $leads = $this->leadService->search($request->q);

        return LeadResource::collection($leads);
    }

    /**
     * Obtém estatísticas dos leads
     */
    public function statistics(Request $request)
    {
        $cacheKey = 'leads:statistics';
        $ttl = config('cache.ttl.dashboard', 300);

        $stats = Cache::remember($cacheKey, $ttl, function () {
            return $this->leadService->getStatistics();
        });

        return $this->successResponse($stats);
    }
}
