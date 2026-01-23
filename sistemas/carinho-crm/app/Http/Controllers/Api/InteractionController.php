<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InteractionRequest;
use App\Http\Resources\InteractionResource;
use App\Models\Interaction;
use App\Services\InteractionService;
use App\Events\InteractionRecorded;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    public function __construct(
        protected InteractionService $interactionService
    ) {}

    /**
     * Lista todas as interações com filtros e paginação
     */
    public function index(Request $request)
    {
        $query = Interaction::with(['lead', 'channel']);

        // Filtros
        if ($request->has('lead_id')) {
            $query->forLead($request->lead_id);
        }

        if ($request->has('channel_id')) {
            $query->byChannel($request->channel_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->occurredBetween($request->start_date, $request->end_date);
        }

        if ($request->has('recent')) {
            $query->recent((int) $request->recent);
        }

        // Ordenação
        $query->latestFirst();

        // Paginação
        $perPage = min($request->get('per_page', 15), 100);
        $interactions = $query->paginate($perPage);

        return InteractionResource::collection($interactions);
    }

    /**
     * Cria uma nova interação
     */
    public function store(InteractionRequest $request)
    {
        $interaction = $this->interactionService->createInteraction($request->validated());

        event(new InteractionRecorded($interaction));

        return $this->createdResponse(
            new InteractionResource($interaction->load(['lead', 'channel'])),
            'Interação registrada com sucesso'
        );
    }

    /**
     * Exibe uma interação específica
     */
    public function show(Interaction $interaction)
    {
        $interaction->load([
            'lead.urgency',
            'lead.serviceType',
            'lead.status',
            'channel',
        ]);

        return new InteractionResource($interaction);
    }

    /**
     * Atualiza uma interação
     */
    public function update(InteractionRequest $request, Interaction $interaction)
    {
        $interaction->update($request->validated());

        return $this->successResponse(
            new InteractionResource($interaction->load(['lead', 'channel'])),
            'Interação atualizada com sucesso'
        );
    }

    /**
     * Remove uma interação
     */
    public function destroy(Interaction $interaction)
    {
        $interaction->delete();

        return $this->successResponse(null, 'Interação excluída com sucesso');
    }

    /**
     * Lista interações de um lead específico
     */
    public function forLead(int $leadId, Request $request)
    {
        $query = Interaction::with(['channel'])
            ->forLead($leadId)
            ->latestFirst();

        // Paginação
        $perPage = min($request->get('per_page', 20), 100);
        $interactions = $query->paginate($perPage);

        return InteractionResource::collection($interactions);
    }

    /**
     * Obtém timeline de interações de um lead
     */
    public function timeline(int $leadId)
    {
        $interactions = Interaction::with(['channel'])
            ->forLead($leadId)
            ->latestFirst()
            ->get()
            ->groupBy(function ($interaction) {
                return $interaction->occurred_at->format('Y-m-d');
            });

        return $this->successResponse($interactions);
    }

    /**
     * Obtém estatísticas de interações
     */
    public function statistics(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());

        $stats = $this->interactionService->getStatistics($startDate, $endDate);

        return $this->successResponse($stats);
    }
}
