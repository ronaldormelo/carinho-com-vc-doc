<?php

namespace App\Http\Controllers;

use App\Services\BrandLibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandLibraryController extends Controller
{
    public function __construct(
        private BrandLibraryService $service
    ) {}

    /**
     * Lista assets da marca.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'active']);

        $assets = $this->service->list($filters);

        return $this->success($assets, 'Assets carregados');
    }

    /**
     * Cria novo asset.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:logo,icon,template,typography,color,pattern',
            'file_url' => 'required|string|url|max:512',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            $asset = $this->service->create($request->all());

            return $this->created($asset->toArray(), 'Asset criado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Exibe asset.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $asset = $this->service->get($id);

            return $this->success($asset->toArray());
        } catch (\Throwable $e) {
            return $this->notFound('Asset nao encontrado');
        }
    }

    /**
     * Atualiza asset.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'file_url' => 'nullable|string|url|max:512',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            $asset = $this->service->update($id, $request->all());

            return $this->success($asset->toArray(), 'Asset atualizado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Desativa asset.
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $asset = $this->service->deactivate($id);

            return $this->success($asset->toArray(), 'Asset desativado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Ativa asset.
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $asset = $this->service->activate($id);

            return $this->success($asset->toArray(), 'Asset ativado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Lista logos.
     */
    public function logos(): JsonResponse
    {
        $logos = $this->service->getLogos();

        return $this->success($logos, 'Logos carregados');
    }

    /**
     * Obtem logo principal.
     */
    public function primaryLogo(): JsonResponse
    {
        $logo = $this->service->getPrimaryLogo();

        if (!$logo) {
            return $this->notFound('Logo principal nao encontrado');
        }

        return $this->success($logo->toArray());
    }

    /**
     * Lista templates.
     */
    public function templates(): JsonResponse
    {
        $templates = $this->service->getTemplates();

        return $this->success($templates, 'Templates carregados');
    }

    /**
     * Obtem configuracoes de branding.
     */
    public function config(): JsonResponse
    {
        $config = $this->service->getBrandingConfig();

        return $this->success($config, 'Configuracoes de branding');
    }

    /**
     * Obtem paleta de cores.
     */
    public function colors(): JsonResponse
    {
        $colors = $this->service->getColorPalette();

        return $this->success($colors, 'Paleta de cores');
    }

    /**
     * Obtem configuracoes de tipografia.
     */
    public function typography(): JsonResponse
    {
        $typography = $this->service->getTypography();

        return $this->success($typography, 'Tipografia');
    }

    /**
     * Obtem tom de voz.
     */
    public function voice(): JsonResponse
    {
        $voice = $this->service->getVoiceGuidelines();

        return $this->success($voice, 'Tom de voz');
    }

    /**
     * Obtem mensagens-chave.
     */
    public function messages(): JsonResponse
    {
        $messages = $this->service->getKeyMessages();

        return $this->success($messages, 'Mensagens-chave');
    }

    /**
     * Obtem hashtags.
     */
    public function hashtags(): JsonResponse
    {
        $hashtags = $this->service->getHashtags();

        return $this->success($hashtags, 'Hashtags');
    }

    /**
     * Obtem bio padrao.
     */
    public function socialBio(): JsonResponse
    {
        $bio = $this->service->getSocialBio();

        return $this->success(['bio' => $bio], 'Bio padrao');
    }

    /**
     * Obtem temas de conteudo.
     */
    public function contentThemes(): JsonResponse
    {
        $themes = $this->service->getContentThemes();

        return $this->success($themes, 'Temas de conteudo');
    }

    /**
     * Gera CSS de branding.
     */
    public function css(): \Illuminate\Http\Response
    {
        $css = $this->service->generateBrandCss();

        return response($css, 200, ['Content-Type' => 'text/css']);
    }
}
