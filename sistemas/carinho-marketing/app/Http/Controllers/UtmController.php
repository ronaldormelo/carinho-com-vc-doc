<?php

namespace App\Http\Controllers;

use App\Services\UtmBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UtmController extends Controller
{
    public function __construct(
        private UtmBuilderService $service
    ) {}

    /**
     * Lista links UTM.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['source', 'medium', 'campaign']);

        $links = $this->service->list($filters);

        return $this->success($links, 'Links UTM carregados');
    }

    /**
     * Cria novo link UTM.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'source' => 'required|string|max:128',
            'medium' => 'required|string|max:128',
            'campaign' => 'required|string|max:128',
            'content' => 'nullable|string|max:128',
            'term' => 'nullable|string|max:128',
        ]);

        try {
            $utm = $this->service->create($request->all());

            return $this->created([
                'utm' => $utm->toArray(),
                'url' => $utm->buildUrl(),
            ], 'Link UTM criado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Exibe link UTM.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $utm = $this->service->get($id);

            return $this->success([
                'utm' => $utm->toArray(),
                'url' => $utm->buildUrl(),
            ]);
        } catch (\Throwable $e) {
            return $this->notFound('Link UTM nao encontrado');
        }
    }

    /**
     * Gera URL com UTM.
     */
    public function buildUrl(Request $request): JsonResponse
    {
        $request->validate([
            'source' => 'required|string',
            'medium' => 'required|string',
            'campaign' => 'required|string',
            'content' => 'nullable|string',
            'term' => 'nullable|string',
            'base_url' => 'nullable|string|url',
        ]);

        $url = $this->service->buildUrl(
            $request->only(['source', 'medium', 'campaign', 'content', 'term']),
            $request->input('base_url')
        );

        return $this->success(['url' => $url], 'URL gerada');
    }

    /**
     * Gera URL para WhatsApp.
     */
    public function buildWhatsAppUrl(Request $request): JsonResponse
    {
        $request->validate([
            'source' => 'required|string',
            'medium' => 'required|string',
            'campaign' => 'required|string',
            'content' => 'nullable|string',
            'term' => 'nullable|string',
            'phone' => 'nullable|string',
            'message' => 'nullable|string',
        ]);

        $url = $this->service->buildWhatsAppUrl(
            $request->only(['source', 'medium', 'campaign', 'content', 'term']),
            $request->input('phone'),
            $request->input('message')
        );

        return $this->success(['url' => $url], 'URL do WhatsApp gerada');
    }

    /**
     * Gera URL para bio de redes sociais.
     */
    public function buildBioUrl(Request $request): JsonResponse
    {
        $request->validate([
            'platform' => 'required|string',
        ]);

        $url = $this->service->buildBioUrl($request->input('platform'));

        return $this->success(['url' => $url], 'URL de bio gerada');
    }

    /**
     * Gera URL para campanha.
     */
    public function buildCampaignUrl(Request $request): JsonResponse
    {
        $request->validate([
            'campaign_name' => 'required|string',
            'source' => 'required|string',
            'medium' => 'required|string',
            'content' => 'nullable|string',
        ]);

        $url = $this->service->buildCampaignUrl(
            $request->input('campaign_name'),
            $request->input('source'),
            $request->input('medium'),
            $request->input('content')
        );

        return $this->success(['url' => $url], 'URL de campanha gerada');
    }

    /**
     * Extrai UTM de uma URL.
     */
    public function parseUrl(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|string|url',
        ]);

        $params = $this->service->parseUrl($request->input('url'));

        return $this->success($params, 'UTM extraido');
    }

    /**
     * Retorna sources disponiveis.
     */
    public function sources(): JsonResponse
    {
        return $this->success(UtmBuilderService::getSources(), 'Sources disponiveis');
    }

    /**
     * Retorna mediums disponiveis.
     */
    public function mediums(): JsonResponse
    {
        return $this->success(UtmBuilderService::getMediums(), 'Mediums disponiveis');
    }
}
