<?php

namespace App\Http\Controllers;

use App\Services\LandingPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function __construct(
        private LandingPageService $service
    ) {}

    /**
     * Lista landing pages.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status_id', 'search']);

        $pages = $this->service->list($filters);

        return $this->success($pages, 'Landing pages carregadas');
    }

    /**
     * Cria nova landing page.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:190|unique:landing_pages,slug',
            'utm' => 'nullable|array',
            'utm.source' => 'required_with:utm|string',
            'utm.medium' => 'required_with:utm|string',
            'utm.campaign' => 'required_with:utm|string',
        ]);

        try {
            $page = $this->service->create($request->all());

            return $this->created($page->toArray(), 'Landing page criada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Exibe landing page.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $page = $this->service->get($id);

            return $this->success($page->toArray());
        } catch (\Throwable $e) {
            return $this->notFound('Landing page nao encontrada');
        }
    }

    /**
     * Atualiza landing page.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:190',
        ]);

        try {
            $page = $this->service->update($id, $request->all());

            return $this->success($page->toArray(), 'Landing page atualizada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Publica landing page.
     */
    public function publish(int $id): JsonResponse
    {
        try {
            $page = $this->service->publish($id);

            return $this->success($page->toArray(), 'Landing page publicada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Arquiva landing page.
     */
    public function archive(int $id): JsonResponse
    {
        try {
            $page = $this->service->archive($id);

            return $this->success($page->toArray(), 'Landing page arquivada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Define UTM padrao.
     */
    public function setUtm(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'source' => 'required|string',
            'medium' => 'required|string',
            'campaign' => 'required|string',
            'content' => 'nullable|string',
            'term' => 'nullable|string',
        ]);

        try {
            $page = $this->service->setDefaultUtm($id, $request->all());

            return $this->success($page->toArray(), 'UTM definido');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Obtem estatisticas da landing page.
     */
    public function stats(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $stats = $this->service->getStats(
                $id,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return $this->success($stats, 'Estatisticas carregadas');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Gera URL com UTM.
     */
    public function generateUrl(Request $request, int $id): JsonResponse
    {
        $customUtm = $request->only(['source', 'medium', 'campaign', 'content', 'term']);

        try {
            $url = $this->service->generateUrl($id, !empty($customUtm) ? $customUtm : null);

            return $this->success(['url' => $url], 'URL gerada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Lista landing pages publicadas.
     */
    public function published(): JsonResponse
    {
        $pages = $this->service->listPublished();

        return $this->success($pages, 'Landing pages publicadas');
    }
}
