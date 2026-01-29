<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Integrations\CarinhoSiteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para gestão de conteúdo do site (testimonials, FAQ, páginas)
 */
class ContentController extends Controller
{
    public function __construct(
        protected CarinhoSiteService $siteService
    ) {}

    // =====================================================================
    // Testimonials
    // =====================================================================

    /**
     * Lista todos os depoimentos
     */
    public function testimonials(Request $request): JsonResponse
    {
        $filters = $request->only(['active', 'featured']);
        $data = $this->siteService->getTestimonials($filters);

        if ($data === null) {
            return response()->json(['error' => 'Erro ao buscar depoimentos'], 500);
        }

        return response()->json($data);
    }

    /**
     * Obtém um depoimento específico
     */
    public function testimonial(int $id): JsonResponse
    {
        $data = $this->siteService->getTestimonial($id);

        if ($data === null) {
            return response()->json(['error' => 'Depoimento não encontrado'], 404);
        }

        return response()->json($data);
    }

    /**
     * Cria um novo depoimento
     */
    public function createTestimonial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'content' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'avatar_url' => 'nullable|url|max:512',
            'featured' => 'boolean',
            'active' => 'boolean',
        ]);

        $data = $this->siteService->createTestimonial($validated);

        if ($data === null) {
            return response()->json(['error' => 'Erro ao criar depoimento'], 500);
        }

        return response()->json($data, 201);
    }

    /**
     * Atualiza um depoimento
     */
    public function updateTestimonial(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role' => 'nullable|string|max:255',
            'content' => 'sometimes|string',
            'rating' => 'sometimes|integer|min:1|max:5',
            'avatar_url' => 'nullable|url|max:512',
            'featured' => 'boolean',
            'active' => 'boolean',
        ]);

        $data = $this->siteService->updateTestimonial($id, $validated);

        if ($data === null) {
            return response()->json(['error' => 'Erro ao atualizar depoimento'], 500);
        }

        return response()->json($data);
    }

    /**
     * Exclui um depoimento
     */
    public function deleteTestimonial(int $id): JsonResponse
    {
        $result = $this->siteService->deleteTestimonial($id);

        if ($result === null) {
            return response()->json(['error' => 'Erro ao excluir depoimento'], 500);
        }

        return response()->json(['message' => 'Depoimento excluído com sucesso']);
    }

    // =====================================================================
    // FAQ Categories
    // =====================================================================

    /**
     * Lista todas as categorias de FAQ
     */
    public function faqCategories(): JsonResponse
    {
        $data = $this->siteService->getFaqCategories();

        if ($data === null) {
            return response()->json(['error' => 'Erro ao buscar categorias'], 500);
        }

        return response()->json($data);
    }

    /**
     * Obtém uma categoria específica
     */
    public function faqCategory(int $id): JsonResponse
    {
        $data = $this->siteService->getFaqCategory($id);

        if ($data === null) {
            return response()->json(['error' => 'Categoria não encontrada'], 404);
        }

        return response()->json($data);
    }

    /**
     * Cria uma nova categoria de FAQ
     */
    public function createFaqCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/',
            'sort_order' => 'integer|min:0',
            'active' => 'boolean',
        ]);

        $data = $this->siteService->createFaqCategory($validated);

        if ($data === null) {
            return response()->json(['error' => 'Erro ao criar categoria'], 500);
        }

        return response()->json($data, 201);
    }

    /**
     * Atualiza uma categoria de FAQ
     */
    public function updateFaqCategory(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|regex:/^[a-z0-9-]+$/',
            'sort_order' => 'sometimes|integer|min:0',
            'active' => 'boolean',
        ]);

        $data = $this->siteService->updateFaqCategory($id, $validated);

        if ($data === null) {
            return response()->json(['error' => 'Erro ao atualizar categoria'], 500);
        }

        return response()->json($data);
    }

    /**
     * Exclui uma categoria de FAQ
     */
    public function deleteFaqCategory(int $id): JsonResponse
    {
        $result = $this->siteService->deleteFaqCategory($id);

        if ($result === null) {
            return response()->json(['error' => 'Erro ao excluir categoria'], 500);
        }

        return response()->json(['message' => 'Categoria excluída com sucesso']);
    }

    // =====================================================================
    // FAQ Items
    // =====================================================================

    /**
     * Lista itens de FAQ de uma categoria
     */
    public function faqItems(int $categoryId): JsonResponse
    {
        $data = $this->siteService->getFaqItems($categoryId);

        if ($data === null) {
            return response()->json(['error' => 'Erro ao buscar itens'], 500);
        }

        return response()->json($data);
    }

    /**
     * Obtém um item de FAQ específico
     */
    public function faqItem(int $categoryId, int $itemId): JsonResponse
    {
        $data = $this->siteService->getFaqItem($categoryId, $itemId);

        if ($data === null) {
            return response()->json(['error' => 'Item não encontrado'], 404);
        }

        return response()->json($data);
    }

    /**
     * Cria um novo item de FAQ
     */
    public function createFaqItem(Request $request, int $categoryId): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'sort_order' => 'integer|min:0',
            'active' => 'boolean',
        ]);

        $data = $this->siteService->createFaqItem($categoryId, $validated);

        if ($data === null) {
            return response()->json(['error' => 'Erro ao criar item'], 500);
        }

        return response()->json($data, 201);
    }

    /**
     * Atualiza um item de FAQ
     */
    public function updateFaqItem(Request $request, int $categoryId, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'sometimes|string|max:500',
            'answer' => 'sometimes|string',
            'sort_order' => 'sometimes|integer|min:0',
            'active' => 'boolean',
        ]);

        $data = $this->siteService->updateFaqItem($categoryId, $itemId, $validated);

        if ($data === null) {
            return response()->json(['error' => 'Erro ao atualizar item'], 500);
        }

        return response()->json($data);
    }

    /**
     * Exclui um item de FAQ
     */
    public function deleteFaqItem(int $categoryId, int $itemId): JsonResponse
    {
        $result = $this->siteService->deleteFaqItem($categoryId, $itemId);

        if ($result === null) {
            return response()->json(['error' => 'Erro ao excluir item'], 500);
        }

        return response()->json(['message' => 'Item excluído com sucesso']);
    }

    // =====================================================================
    // Pages
    // =====================================================================

    /**
     * Lista todas as páginas
     */
    public function pages(Request $request): JsonResponse
    {
        $filters = $request->only(['status_id', 'slug']);
        $data = $this->siteService->getPages($filters);

        if ($data === null) {
            return response()->json(['error' => 'Erro ao buscar páginas'], 500);
        }

        return response()->json($data);
    }

    /**
     * Obtém uma página específica
     */
    public function page(int $id): JsonResponse
    {
        $data = $this->siteService->getPage($id);

        if ($data === null) {
            return response()->json(['error' => 'Página não encontrada'], 404);
        }

        return response()->json($data);
    }

    /**
     * Cria uma nova página
     */
    public function createPage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:190|regex:/^[a-z0-9-]+$/',
            'title' => 'required|string|max:255',
            'status_id' => 'required|integer',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:512',
            'seo_keywords' => 'nullable|string',
            'content_json' => 'required|array',
            'published_at' => 'nullable|date',
        ]);

        $data = $this->siteService->createPage($validated);

        if ($data === null) {
            return response()->json(['error' => 'Erro ao criar página'], 500);
        }

        return response()->json($data, 201);
    }

    /**
     * Atualiza uma página
     */
    public function updatePage(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'sometimes|string|max:190|regex:/^[a-z0-9-]+$/',
            'title' => 'sometimes|string|max:255',
            'status_id' => 'sometimes|integer',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:512',
            'seo_keywords' => 'nullable|string',
            'content_json' => 'sometimes|array',
            'published_at' => 'nullable|date',
        ]);

        $data = $this->siteService->updatePage($id, $validated);

        if ($data === null) {
            return response()->json(['error' => 'Erro ao atualizar página'], 500);
        }

        return response()->json($data);
    }

    /**
     * Exclui uma página
     */
    public function deletePage(int $id): JsonResponse
    {
        $result = $this->siteService->deletePage($id);

        if ($result === null) {
            return response()->json(['error' => 'Erro ao excluir página'], 500);
        }

        return response()->json(['message' => 'Página excluída com sucesso']);
    }

    /**
     * Limpa cache de uma página
     */
    public function clearPageCache(Request $request): JsonResponse
    {
        $slug = $request->input('slug');
        $result = $this->siteService->clearPageCache($slug);

        if ($result === null) {
            return response()->json(['error' => 'Erro ao limpar cache'], 500);
        }

        return response()->json($result);
    }
}
