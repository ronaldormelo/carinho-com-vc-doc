<?php

namespace App\Http\Controllers;

use App\Services\ContentCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentCalendarController extends Controller
{
    public function __construct(
        private ContentCalendarService $service
    ) {}

    /**
     * Lista itens do calendario.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'channel_id' => 'nullable|integer',
        ]);

        $items = $this->service->listByPeriod(
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('channel_id')
        );

        return $this->success($items, 'Calendario carregado');
    }

    /**
     * Lista itens da semana atual.
     */
    public function thisWeek(Request $request): JsonResponse
    {
        $items = $this->service->getThisWeek($request->input('channel_id'));

        return $this->success($items, 'Itens da semana carregados');
    }

    /**
     * Cria novo item no calendario.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'channel_id' => 'required|integer|exists:marketing_channels,id',
            'title' => 'required|string|max:255',
            'scheduled_at' => 'nullable|date',
            'owner_id' => 'nullable|integer',
            'assets' => 'nullable|array',
            'assets.*.type_id' => 'required_with:assets|integer',
            'assets.*.url' => 'required_with:assets|string|url',
            'assets.*.caption' => 'nullable|string',
        ]);

        try {
            $content = $this->service->create($request->all());

            return $this->created($content->toArray(), 'Item criado no calendario');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Exibe item do calendario.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $content = \App\Models\ContentCalendar::with(['channel', 'status', 'assets'])
                ->findOrFail($id);

            return $this->success($content->toArray());
        } catch (\Throwable $e) {
            return $this->notFound('Item nao encontrado');
        }
    }

    /**
     * Atualiza item do calendario.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'channel_id' => 'nullable|integer|exists:marketing_channels,id',
            'title' => 'nullable|string|max:255',
            'scheduled_at' => 'nullable|date',
            'owner_id' => 'nullable|integer',
        ]);

        try {
            $content = $this->service->update($id, $request->all());

            return $this->success($content->toArray(), 'Item atualizado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Agenda conteudo para publicacao.
     */
    public function schedule(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        try {
            $content = $this->service->schedule($id, $request->input('scheduled_at'));

            return $this->success($content->toArray(), 'Conteudo agendado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Cancela agendamento.
     */
    public function cancelSchedule(int $id): JsonResponse
    {
        try {
            $content = $this->service->cancelSchedule($id);

            return $this->success($content->toArray(), 'Agendamento cancelado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Publica conteudo.
     */
    public function publish(int $id): JsonResponse
    {
        try {
            $result = $this->service->publish($id);

            return $this->success($result, 'Conteudo publicado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Adiciona asset ao conteudo.
     */
    public function addAsset(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'type_id' => 'required|integer',
            'url' => 'required|string|url',
            'caption' => 'nullable|string',
        ]);

        try {
            $asset = $this->service->addAsset($id, $request->all());

            return $this->created($asset->toArray(), 'Asset adicionado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Remove asset do conteudo.
     */
    public function removeAsset(int $id, int $assetId): JsonResponse
    {
        try {
            $this->service->removeAsset($id, $assetId);

            return $this->success(null, 'Asset removido');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Aprova asset.
     */
    public function approveAsset(int $assetId): JsonResponse
    {
        try {
            $asset = $this->service->approveAsset($assetId);

            return $this->success($asset->toArray(), 'Asset aprovado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Estatisticas do calendario.
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->service->getStats(
            $request->input('start_date'),
            $request->input('end_date')
        );

        return $this->success($stats, 'Estatisticas carregadas');
    }
}
