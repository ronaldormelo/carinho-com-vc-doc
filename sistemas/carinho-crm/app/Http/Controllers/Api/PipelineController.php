<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PipelineStageResource;
use App\Http\Resources\DealResource;
use App\Models\PipelineStage;
use App\Models\Deal;
use App\Models\Domain\DomainDealStatus;
use App\Services\PipelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PipelineController extends Controller
{
    public function __construct(
        protected PipelineService $pipelineService
    ) {}

    /**
     * Lista todos os estágios do pipeline
     */
    public function stages()
    {
        $stages = PipelineStage::active()
            ->ordered()
            ->get();

        return PipelineStageResource::collection($stages);
    }

    /**
     * Obtém visão completa do pipeline (Kanban)
     */
    public function board(Request $request)
    {
        $cacheKey = 'pipeline:board';
        $ttl = config('cache.ttl.pipeline', 60);

        // Se houver filtros, não usar cache
        if ($request->hasAny(['value_min', 'lead_id', 'start_date'])) {
            $board = $this->pipelineService->getBoard($request->all());
        } else {
            $board = Cache::remember($cacheKey, $ttl, function () {
                return $this->pipelineService->getBoard();
            });
        }

        return $this->successResponse($board);
    }

    /**
     * Obtém deals de um estágio específico
     */
    public function stageDeals(PipelineStage $stage, Request $request)
    {
        $query = Deal::with(['lead', 'status', 'proposals'])
            ->where('stage_id', $stage->id)
            ->where('status_id', DomainDealStatus::OPEN);

        // Filtros opcionais
        if ($request->has('min_value')) {
            $query->withMinValue($request->min_value);
        }

        // Ordenação
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginação
        $perPage = min($request->get('per_page', 20), 100);
        $deals = $query->paginate($perPage);

        return DealResource::collection($deals);
    }

    /**
     * Obtém métricas do pipeline
     */
    public function metrics(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());

        $cacheKey = "pipeline:metrics:{$startDate}:{$endDate}";
        $ttl = config('cache.ttl.dashboard', 300);

        $metrics = Cache::remember($cacheKey, $ttl, function () use ($startDate, $endDate) {
            return $this->pipelineService->getMetrics($startDate, $endDate);
        });

        return $this->successResponse($metrics);
    }

    /**
     * Obtém taxa de conversão por estágio
     */
    public function conversionRates(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());

        $rates = $this->pipelineService->getConversionRates($startDate, $endDate);

        return $this->successResponse($rates);
    }

    /**
     * Obtém tempo médio em cada estágio
     */
    public function stageDuration(Request $request)
    {
        $startDate = $request->get('start_date', now()->subMonths(3));
        $endDate = $request->get('end_date', now());

        $duration = $this->pipelineService->getStageDuration($startDate, $endDate);

        return $this->successResponse($duration);
    }

    /**
     * Obtém forecast de vendas
     */
    public function forecast()
    {
        $forecast = $this->pipelineService->getForecast();

        return $this->successResponse($forecast);
    }

    /**
     * Cria um novo estágio (admin)
     */
    public function createStage(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:64',
            'stage_order' => 'required|integer|min:1',
        ]);

        $stage = PipelineStage::create($request->only(['name', 'stage_order']));
        PipelineStage::clearCache();

        return $this->createdResponse(
            new PipelineStageResource($stage),
            'Estágio criado com sucesso'
        );
    }

    /**
     * Atualiza um estágio (admin)
     */
    public function updateStage(Request $request, PipelineStage $stage)
    {
        $request->validate([
            'name' => 'sometimes|string|max:64',
            'stage_order' => 'sometimes|integer|min:1',
            'active' => 'sometimes|boolean',
        ]);

        $stage->update($request->only(['name', 'stage_order', 'active']));
        PipelineStage::clearCache();

        return $this->successResponse(
            new PipelineStageResource($stage),
            'Estágio atualizado com sucesso'
        );
    }

    /**
     * Reordena os estágios (admin)
     */
    public function reorderStages(Request $request)
    {
        $request->validate([
            'stages' => 'required|array',
            'stages.*.id' => 'required|exists:pipeline_stages,id',
            'stages.*.order' => 'required|integer|min:1',
        ]);

        $this->pipelineService->reorderStages($request->stages);

        return $this->successResponse(null, 'Estágios reordenados com sucesso');
    }
}
