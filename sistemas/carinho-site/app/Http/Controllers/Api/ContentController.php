<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Models\FaqCategory;
use App\Models\FaqItem;
use App\Models\SitePage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Controller API para gestão de conteúdo (chamado pelo CRM)
 */
class ContentController extends Controller
{
    /**
     * Middleware para autenticação via API Key
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $apiKey = $request->header('X-API-Key');
            $expectedKey = config('integrations.internal_token');

            if (empty($expectedKey) || $apiKey !== $expectedKey) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return $next($request);
        });
    }

    // =====================================================================
    // Testimonials
    // =====================================================================

    /**
     * Lista todos os depoimentos
     */
    public function testimonials(Request $request): JsonResponse
    {
        $query = Testimonial::query();

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($request->has('featured')) {
            $query->where('featured', $request->boolean('featured'));
        }

        $testimonials = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $testimonials,
            'count' => $testimonials->count(),
        ]);
    }

    /**
     * Obtém um depoimento específico
     */
    public function testimonial(int $id): JsonResponse
    {
        $testimonial = Testimonial::find($id);

        if (!$testimonial) {
            return response()->json(['error' => 'Depoimento não encontrado'], 404);
        }

        return response()->json(['data' => $testimonial]);
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

        $testimonial = Testimonial::create($validated);

        return response()->json(['data' => $testimonial], 201);
    }

    /**
     * Atualiza um depoimento
     */
    public function updateTestimonial(Request $request, int $id): JsonResponse
    {
        $testimonial = Testimonial::find($id);

        if (!$testimonial) {
            return response()->json(['error' => 'Depoimento não encontrado'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role' => 'nullable|string|max:255',
            'content' => 'sometimes|string',
            'rating' => 'sometimes|integer|min:1|max:5',
            'avatar_url' => 'nullable|url|max:512',
            'featured' => 'boolean',
            'active' => 'boolean',
        ]);

        $testimonial->update($validated);

        return response()->json(['data' => $testimonial]);
    }

    /**
     * Exclui um depoimento
     */
    public function deleteTestimonial(int $id): JsonResponse
    {
        $testimonial = Testimonial::find($id);

        if (!$testimonial) {
            return response()->json(['error' => 'Depoimento não encontrado'], 404);
        }

        $testimonial->delete();

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
        $categories = FaqCategory::with('items')->orderBy('sort_order')->get();

        return response()->json([
            'data' => $categories,
            'count' => $categories->count(),
        ]);
    }

    /**
     * Obtém uma categoria específica
     */
    public function faqCategory(int $id): JsonResponse
    {
        $category = FaqCategory::with('items')->find($id);

        if (!$category) {
            return response()->json(['error' => 'Categoria não encontrada'], 404);
        }

        return response()->json(['data' => $category]);
    }

    /**
     * Cria uma nova categoria de FAQ
     */
    public function createFaqCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:faq_categories,slug',
            'sort_order' => 'integer|min:0',
            'active' => 'boolean',
        ]);

        $category = FaqCategory::create($validated);

        return response()->json(['data' => $category], 201);
    }

    /**
     * Atualiza uma categoria de FAQ
     */
    public function updateFaqCategory(Request $request, int $id): JsonResponse
    {
        $category = FaqCategory::find($id);

        if (!$category) {
            return response()->json(['error' => 'Categoria não encontrada'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:faq_categories,slug,' . $id,
            'sort_order' => 'sometimes|integer|min:0',
            'active' => 'boolean',
        ]);

        $category->update($validated);

        return response()->json(['data' => $category]);
    }

    /**
     * Exclui uma categoria de FAQ
     */
    public function deleteFaqCategory(int $id): JsonResponse
    {
        $category = FaqCategory::find($id);

        if (!$category) {
            return response()->json(['error' => 'Categoria não encontrada'], 404);
        }

        // Exclui todos os itens da categoria
        $category->items()->delete();
        $category->delete();

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
        $category = FaqCategory::find($categoryId);

        if (!$category) {
            return response()->json(['error' => 'Categoria não encontrada'], 404);
        }

        $items = $category->items()->orderBy('sort_order')->get();

        return response()->json([
            'data' => $items,
            'count' => $items->count(),
        ]);
    }

    /**
     * Obtém um item de FAQ específico
     */
    public function faqItem(int $categoryId, int $itemId): JsonResponse
    {
        $item = FaqItem::where('category_id', $categoryId)
            ->where('id', $itemId)
            ->first();

        if (!$item) {
            return response()->json(['error' => 'Item não encontrado'], 404);
        }

        return response()->json(['data' => $item]);
    }

    /**
     * Cria um novo item de FAQ
     */
    public function createFaqItem(Request $request, int $categoryId): JsonResponse
    {
        $category = FaqCategory::find($categoryId);

        if (!$category) {
            return response()->json(['error' => 'Categoria não encontrada'], 404);
        }

        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'sort_order' => 'integer|min:0',
            'active' => 'boolean',
        ]);

        $validated['category_id'] = $categoryId;
        $item = FaqItem::create($validated);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Atualiza um item de FAQ
     */
    public function updateFaqItem(Request $request, int $categoryId, int $itemId): JsonResponse
    {
        $item = FaqItem::where('category_id', $categoryId)
            ->where('id', $itemId)
            ->first();

        if (!$item) {
            return response()->json(['error' => 'Item não encontrado'], 404);
        }

        $validated = $request->validate([
            'question' => 'sometimes|string|max:500',
            'answer' => 'sometimes|string',
            'sort_order' => 'sometimes|integer|min:0',
            'active' => 'boolean',
        ]);

        $item->update($validated);

        return response()->json(['data' => $item]);
    }

    /**
     * Exclui um item de FAQ
     */
    public function deleteFaqItem(int $categoryId, int $itemId): JsonResponse
    {
        $item = FaqItem::where('category_id', $categoryId)
            ->where('id', $itemId)
            ->first();

        if (!$item) {
            return response()->json(['error' => 'Item não encontrado'], 404);
        }

        $item->delete();

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
        $query = SitePage::with('sections');

        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->has('slug')) {
            $query->where('slug', $request->slug);
        }

        $pages = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $pages,
            'count' => $pages->count(),
        ]);
    }

    /**
     * Obtém uma página específica
     */
    public function page(int $id): JsonResponse
    {
        $page = SitePage::with('sections')->find($id);

        if (!$page) {
            return response()->json(['error' => 'Página não encontrada'], 404);
        }

        return response()->json(['data' => $page]);
    }

    /**
     * Cria uma nova página
     */
    public function createPage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:190|unique:site_pages,slug',
            'title' => 'required|string|max:255',
            'status_id' => 'required|integer|exists:domain_page_status,id',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:512',
            'seo_keywords' => 'nullable|string',
            'content_json' => 'required|array',
            'published_at' => 'nullable|date',
        ]);

        $page = SitePage::create($validated);

        return response()->json(['data' => $page], 201);
    }

    /**
     * Atualiza uma página
     */
    public function updatePage(Request $request, int $id): JsonResponse
    {
        $page = SitePage::find($id);

        if (!$page) {
            return response()->json(['error' => 'Página não encontrada'], 404);
        }

        $validated = $request->validate([
            'slug' => 'sometimes|string|max:190|unique:site_pages,slug,' . $id,
            'title' => 'sometimes|string|max:255',
            'status_id' => 'sometimes|integer|exists:domain_page_status,id',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:512',
            'seo_keywords' => 'nullable|string',
            'content_json' => 'sometimes|array',
            'published_at' => 'nullable|date',
        ]);

        $page->update($validated);
        $page->clearCache();

        return response()->json(['data' => $page]);
    }

    /**
     * Exclui uma página
     */
    public function deletePage(int $id): JsonResponse
    {
        $page = SitePage::find($id);

        if (!$page) {
            return response()->json(['error' => 'Página não encontrada'], 404);
        }

        $page->clearCache();
        $page->delete();

        return response()->json(['message' => 'Página excluída com sucesso']);
    }
}
