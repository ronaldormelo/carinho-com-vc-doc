<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DealRequest;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use App\Models\Domain\DomainDealStatus;
use App\Services\DealService;
use App\Events\DealCreated;
use App\Events\DealStageChanged;
use App\Events\DealWon;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function __construct(
        protected DealService $dealService
    ) {}

    /**
     * Lista todos os deals com filtros e paginação
     */
    public function index(Request $request)
    {
        $query = Deal::with(['lead', 'stage', 'status']);

        // Filtros
        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->has('stage_id')) {
            $query->inStage($request->stage_id);
        }

        if ($request->has('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        if ($request->has('open_only') && $request->open_only) {
            $query->open();
        }

        if ($request->has('min_value')) {
            $query->withMinValue($request->min_value);
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
        $deals = $query->paginate($perPage);

        return DealResource::collection($deals);
    }

    /**
     * Cria um novo deal
     */
    public function store(DealRequest $request)
    {
        $deal = $this->dealService->createDeal($request->validated());

        event(new DealCreated($deal));

        return $this->createdResponse(
            new DealResource($deal->load(['lead', 'stage', 'status'])),
            'Negócio criado com sucesso'
        );
    }

    /**
     * Exibe um deal específico
     */
    public function show(Deal $deal)
    {
        $deal->load([
            'lead.urgency',
            'lead.serviceType',
            'lead.status',
            'stage',
            'status',
            'proposals.serviceType',
            'proposals.contract',
        ]);

        return new DealResource($deal);
    }

    /**
     * Atualiza um deal
     */
    public function update(DealRequest $request, Deal $deal)
    {
        $oldStageId = $deal->stage_id;

        $deal = $this->dealService->updateDeal($deal, $request->validated());

        // Verificar se o estágio mudou
        if ($request->has('stage_id') && $oldStageId !== $deal->stage_id) {
            event(new DealStageChanged($deal, $oldStageId));
        }

        return $this->successResponse(
            new DealResource($deal->load(['lead', 'stage', 'status'])),
            'Negócio atualizado com sucesso'
        );
    }

    /**
     * Remove um deal
     */
    public function destroy(Deal $deal)
    {
        if (!$deal->isOpen()) {
            return $this->errorResponse(
                'Não é possível excluir um negócio que já foi finalizado',
                422
            );
        }

        $deal->delete();

        return $this->successResponse(null, 'Negócio excluído com sucesso');
    }

    /**
     * Move o deal para o próximo estágio
     */
    public function moveToNextStage(Deal $deal)
    {
        if (!$deal->isOpen()) {
            return $this->errorResponse('Negócio não está aberto', 422);
        }

        $oldStageId = $deal->stage_id;

        if (!$deal->moveToNextStage()) {
            return $this->errorResponse('Não é possível avançar o estágio', 422);
        }

        event(new DealStageChanged($deal, $oldStageId));

        return $this->successResponse(
            new DealResource($deal->load(['lead', 'stage', 'status'])),
            'Negócio avançado para o próximo estágio'
        );
    }

    /**
     * Move o deal para um estágio específico
     */
    public function moveToStage(Request $request, Deal $deal)
    {
        $request->validate([
            'stage_id' => 'required|exists:pipeline_stages,id',
        ]);

        if (!$deal->canMoveToStage($request->stage_id)) {
            return $this->errorResponse('Não é possível mover para este estágio', 422);
        }

        $oldStageId = $deal->stage_id;
        $deal->stage_id = $request->stage_id;
        $deal->save();

        event(new DealStageChanged($deal, $oldStageId));

        return $this->successResponse(
            new DealResource($deal->load(['lead', 'stage', 'status'])),
            'Negócio movido com sucesso'
        );
    }

    /**
     * Marca o deal como ganho
     */
    public function markAsWon(Deal $deal)
    {
        if (!$deal->isOpen()) {
            return $this->errorResponse('Negócio não está aberto', 422);
        }

        $deal->status_id = DomainDealStatus::WON;
        $deal->save();

        event(new DealWon($deal));

        return $this->successResponse(
            new DealResource($deal->load(['lead', 'stage', 'status'])),
            'Negócio marcado como ganho'
        );
    }

    /**
     * Marca o deal como perdido
     */
    public function markAsLost(Deal $deal)
    {
        if (!$deal->isOpen()) {
            return $this->errorResponse('Negócio não está aberto', 422);
        }

        $deal->status_id = DomainDealStatus::LOST;
        $deal->save();

        return $this->successResponse(
            new DealResource($deal->load(['lead', 'stage', 'status'])),
            'Negócio marcado como perdido'
        );
    }
}
