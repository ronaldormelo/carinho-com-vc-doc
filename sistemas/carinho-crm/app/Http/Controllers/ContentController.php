<?php

namespace App\Http\Controllers;

use App\Services\Integrations\CarinhoSiteService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Controller Web para gestão de conteúdo do site
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
    public function testimonials(): View
    {
        $testimonials = $this->siteService->getTestimonials() ?? [];

        return view('content.testimonials.index', [
            'testimonials' => $testimonials['data'] ?? [],
        ]);
    }

    /**
     * Formulário de criação/edição de depoimento
     */
    public function testimonialForm(?int $id = null): View
    {
        $testimonial = null;
        if ($id) {
            $data = $this->siteService->getTestimonial($id);
            $testimonial = $data['data'] ?? null;
        }

        return view('content.testimonials.form', [
            'testimonial' => $testimonial,
        ]);
    }

    /**
     * Salva um depoimento (cria ou atualiza)
     */
    public function saveTestimonial(Request $request, ?int $id = null): RedirectResponse
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

        if ($id) {
            $result = $this->siteService->updateTestimonial($id, $validated);
            $message = 'Depoimento atualizado com sucesso!';
        } else {
            $result = $this->siteService->createTestimonial($validated);
            $message = 'Depoimento criado com sucesso!';
        }

        if ($result === null) {
            return back()->withErrors(['error' => 'Erro ao salvar depoimento'])->withInput();
        }

        return redirect()->route('content.testimonials')->with('success', $message);
    }

    /**
     * Exclui um depoimento
     */
    public function deleteTestimonial(int $id): RedirectResponse
    {
        $result = $this->siteService->deleteTestimonial($id);

        if ($result === null) {
            return back()->withErrors(['error' => 'Erro ao excluir depoimento']);
        }

        return redirect()->route('content.testimonials')->with('success', 'Depoimento excluído com sucesso!');
    }

    // =====================================================================
    // FAQ Categories
    // =====================================================================

    /**
     * Lista todas as categorias de FAQ
     */
    public function faqCategories(): View
    {
        $categories = $this->siteService->getFaqCategories() ?? [];

        return view('content.faq.categories.index', [
            'categories' => $categories['data'] ?? [],
        ]);
    }

    /**
     * Formulário de criação/edição de categoria
     */
    public function faqCategoryForm(?int $id = null): View
    {
        $category = null;
        if ($id) {
            $data = $this->siteService->getFaqCategory($id);
            $category = $data['data'] ?? null;
        }

        return view('content.faq.categories.form', [
            'category' => $category,
        ]);
    }

    /**
     * Salva uma categoria (cria ou atualiza)
     */
    public function saveFaqCategory(Request $request, ?int $id = null): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/',
            'sort_order' => 'integer|min:0',
            'active' => 'boolean',
        ]);

        if ($id) {
            $result = $this->siteService->updateFaqCategory($id, $validated);
            $message = 'Categoria atualizada com sucesso!';
        } else {
            $result = $this->siteService->createFaqCategory($validated);
            $message = 'Categoria criada com sucesso!';
        }

        if ($result === null) {
            return back()->withErrors(['error' => 'Erro ao salvar categoria'])->withInput();
        }

        return redirect()->route('content.faq.categories')->with('success', $message);
    }

    /**
     * Exclui uma categoria
     */
    public function deleteFaqCategory(int $id): RedirectResponse
    {
        $result = $this->siteService->deleteFaqCategory($id);

        if ($result === null) {
            return back()->withErrors(['error' => 'Erro ao excluir categoria']);
        }

        return redirect()->route('content.faq.categories')->with('success', 'Categoria excluída com sucesso!');
    }

    // =====================================================================
    // FAQ Items
    // =====================================================================

    /**
     * Lista itens de FAQ de uma categoria
     */
    public function faqItems(int $categoryId): View
    {
        $category = $this->siteService->getFaqCategory($categoryId);
        $items = $this->siteService->getFaqItems($categoryId) ?? [];

        return view('content.faq.items.index', [
            'category' => $category['data'] ?? null,
            'items' => $items['data'] ?? [],
        ]);
    }

    /**
     * Formulário de criação/edição de item de FAQ
     */
    public function faqItemForm(int $categoryId, ?int $itemId = null): View
    {
        $category = $this->siteService->getFaqCategory($categoryId);
        $item = null;

        if ($itemId) {
            $data = $this->siteService->getFaqItem($categoryId, $itemId);
            $item = $data['data'] ?? null;
        }

        return view('content.faq.items.form', [
            'category' => $category['data'] ?? null,
            'item' => $item,
        ]);
    }

    /**
     * Salva um item de FAQ (cria ou atualiza)
     */
    public function saveFaqItem(Request $request, int $categoryId, ?int $itemId = null): RedirectResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'sort_order' => 'integer|min:0',
            'active' => 'boolean',
        ]);

        if ($itemId) {
            $result = $this->siteService->updateFaqItem($categoryId, $itemId, $validated);
            $message = 'Item atualizado com sucesso!';
        } else {
            $result = $this->siteService->createFaqItem($categoryId, $validated);
            $message = 'Item criado com sucesso!';
        }

        if ($result === null) {
            return back()->withErrors(['error' => 'Erro ao salvar item'])->withInput();
        }

        return redirect()->route('content.faq.items', $categoryId)->with('success', $message);
    }

    /**
     * Exclui um item de FAQ
     */
    public function deleteFaqItem(int $categoryId, int $itemId): RedirectResponse
    {
        $result = $this->siteService->deleteFaqItem($categoryId, $itemId);

        if ($result === null) {
            return back()->withErrors(['error' => 'Erro ao excluir item']);
        }

        return redirect()->route('content.faq.items', $categoryId)->with('success', 'Item excluído com sucesso!');
    }

    // =====================================================================
    // Pages
    // =====================================================================

    /**
     * Lista todas as páginas
     */
    public function pages(): View
    {
        $pages = $this->siteService->getPages() ?? [];

        return view('content.pages.index', [
            'pages' => $pages['data'] ?? [],
        ]);
    }

    /**
     * Formulário de criação/edição de página
     */
    public function pageForm(?int $id = null): View
    {
        $page = null;
        if ($id) {
            $data = $this->siteService->getPage($id);
            $page = $data['data'] ?? null;
        }

        return view('content.pages.form', [
            'page' => $page,
        ]);
    }

    /**
     * Salva uma página (cria ou atualiza)
     */
    public function savePage(Request $request, ?int $id = null): RedirectResponse
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

        if ($id) {
            $result = $this->siteService->updatePage($id, $validated);
            $message = 'Página atualizada com sucesso!';
        } else {
            $result = $this->siteService->createPage($validated);
            $message = 'Página criada com sucesso!';
        }

        if ($result === null) {
            return back()->withErrors(['error' => 'Erro ao salvar página'])->withInput();
        }

        // Limpa cache da página
        $this->siteService->clearPageCache($validated['slug']);

        return redirect()->route('content.pages')->with('success', $message);
    }

    /**
     * Exclui uma página
     */
    public function deletePage(int $id): RedirectResponse
    {
        $result = $this->siteService->deletePage($id);

        if ($result === null) {
            return back()->withErrors(['error' => 'Erro ao excluir página']);
        }

        return redirect()->route('content.pages')->with('success', 'Página excluída com sucesso!');
    }
}
