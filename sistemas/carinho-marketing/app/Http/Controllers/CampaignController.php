<?php

namespace App\Http\Controllers;

use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(
        private CampaignService $service
    ) {}

    /**
     * Lista campanhas.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['channel_id', 'status_id', 'start_date', 'end_date']);

        $campaigns = $this->service->list($filters);

        return $this->success($campaigns, 'Campanhas carregadas');
    }

    /**
     * Cria nova campanha.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'channel_id' => 'required|integer|exists:marketing_channels,id',
            'name' => 'required|string|max:255',
            'objective' => 'required|string|max:255',
            'budget' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $campaign = $this->service->create($request->all());

            return $this->created($campaign->toArray(), 'Campanha criada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Exibe campanha com detalhes.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $data = $this->service->get($id);

            return $this->success($data);
        } catch (\Throwable $e) {
            return $this->notFound('Campanha nao encontrada');
        }
    }

    /**
     * Atualiza campanha.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'objective' => 'nullable|string|max:255',
            'budget' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $campaign = $this->service->update($id, $request->all());

            return $this->success($campaign->toArray(), 'Campanha atualizada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Ativa campanha.
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $campaign = $this->service->activate($id);

            return $this->success($campaign->toArray(), 'Campanha ativada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Pausa campanha.
     */
    public function pause(int $id): JsonResponse
    {
        try {
            $campaign = $this->service->pause($id);

            return $this->success($campaign->toArray(), 'Campanha pausada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Finaliza campanha.
     */
    public function finish(int $id): JsonResponse
    {
        try {
            $campaign = $this->service->finish($id);

            return $this->success($campaign->toArray(), 'Campanha finalizada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Adiciona grupo de anuncios.
     */
    public function addAdGroup(Request $request, int $campaignId): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'targeting' => 'nullable|array',
        ]);

        try {
            $adGroup = $this->service->addAdGroup($campaignId, $request->all());

            return $this->created($adGroup->toArray(), 'Grupo de anuncios criado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Atualiza grupo de anuncios.
     */
    public function updateAdGroup(Request $request, int $adGroupId): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'targeting' => 'nullable|array',
        ]);

        try {
            $adGroup = $this->service->updateAdGroup($adGroupId, $request->all());

            return $this->success($adGroup->toArray(), 'Grupo de anuncios atualizado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Adiciona criativo ao grupo.
     */
    public function addCreative(Request $request, int $adGroupId): JsonResponse
    {
        $request->validate([
            'type_id' => 'required|integer',
            'headline' => 'required|string|max:255',
            'body' => 'required|string',
            'media_url' => 'nullable|string|url',
        ]);

        try {
            $creative = $this->service->addCreative($adGroupId, $request->all());

            return $this->created($creative->toArray(), 'Criativo adicionado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Sincroniza metricas da plataforma.
     */
    public function syncMetrics(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $metrics = $this->service->syncMetrics(
                $id,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return $this->success($metrics, 'Metricas sincronizadas');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Obtem metricas da campanha.
     */
    public function metrics(Request $request, int $id): JsonResponse
    {
        $metrics = $this->service->getMetrics(
            $id,
            $request->input('start_date'),
            $request->input('end_date')
        );

        return $this->success($metrics, 'Metricas carregadas');
    }

    /**
     * Obtem metricas diarias.
     */
    public function dailyMetrics(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $metrics = $this->service->getDailyMetrics(
            $id,
            $request->input('start_date'),
            $request->input('end_date')
        );

        return $this->success($metrics, 'Metricas diarias carregadas');
    }

    /**
     * Dashboard de campanhas.
     */
    public function dashboard(): JsonResponse
    {
        $data = $this->service->getDashboard();

        return $this->success($data, 'Dashboard carregado');
    }
}
