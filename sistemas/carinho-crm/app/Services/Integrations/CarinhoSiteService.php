<?php

namespace App\Services\Integrations;

/**
 * Integração com Carinho Site (site.carinho.com.vc)
 * Recebe leads de formulários e integra UTM tracking
 */
class CarinhoSiteService extends BaseInternalService
{
    protected string $serviceName = 'carinho-site';

    public function isEnabled(): bool
    {
        return config('integrations.internal.site.enabled', true) 
            && !empty($this->apiKey);
    }

    /**
     * Notifica o site sobre conversão de lead
     */
    public function notifyLeadConverted(int $leadId, array $leadData): ?array
    {
        return $this->post('webhooks/lead-converted', [
            'lead_id' => $leadId,
            'name' => $leadData['name'],
            'converted_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Obtém dados de UTM por ID
     */
    public function getUtmData(int $utmId): ?array
    {
        return $this->get("utm/{$utmId}");
    }

    /**
     * Registra conversão para tracking
     */
    public function trackConversion(int $leadId, string $conversionType, ?float $value = null): ?array
    {
        return $this->post('analytics/conversion', [
            'lead_id' => $leadId,
            'type' => $conversionType,
            'value' => $value,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    // =====================================================================
    // Gestão de Conteúdo - Testimonials
    // =====================================================================

    /**
     * Lista todos os depoimentos
     */
    public function getTestimonials(array $filters = []): ?array
    {
        return $this->get('content/testimonials', $filters);
    }

    /**
     * Obtém um depoimento específico
     */
    public function getTestimonial(int $id): ?array
    {
        return $this->get("content/testimonials/{$id}");
    }

    /**
     * Cria um novo depoimento
     */
    public function createTestimonial(array $data): ?array
    {
        return $this->post('content/testimonials', $data);
    }

    /**
     * Atualiza um depoimento
     */
    public function updateTestimonial(int $id, array $data): ?array
    {
        return $this->put("content/testimonials/{$id}", $data);
    }

    /**
     * Exclui um depoimento
     */
    public function deleteTestimonial(int $id): ?array
    {
        return $this->delete("content/testimonials/{$id}");
    }

    // =====================================================================
    // Gestão de Conteúdo - FAQ
    // =====================================================================

    /**
     * Lista todas as categorias de FAQ
     */
    public function getFaqCategories(): ?array
    {
        return $this->get('content/faq/categories');
    }

    /**
     * Obtém uma categoria específica
     */
    public function getFaqCategory(int $id): ?array
    {
        return $this->get("content/faq/categories/{$id}");
    }

    /**
     * Cria uma nova categoria de FAQ
     */
    public function createFaqCategory(array $data): ?array
    {
        return $this->post('content/faq/categories', $data);
    }

    /**
     * Atualiza uma categoria de FAQ
     */
    public function updateFaqCategory(int $id, array $data): ?array
    {
        return $this->put("content/faq/categories/{$id}", $data);
    }

    /**
     * Exclui uma categoria de FAQ
     */
    public function deleteFaqCategory(int $id): ?array
    {
        return $this->delete("content/faq/categories/{$id}");
    }

    /**
     * Lista itens de FAQ de uma categoria
     */
    public function getFaqItems(int $categoryId): ?array
    {
        return $this->get("content/faq/categories/{$categoryId}/items");
    }

    /**
     * Obtém um item de FAQ específico
     */
    public function getFaqItem(int $categoryId, int $itemId): ?array
    {
        return $this->get("content/faq/categories/{$categoryId}/items/{$itemId}");
    }

    /**
     * Cria um novo item de FAQ
     */
    public function createFaqItem(int $categoryId, array $data): ?array
    {
        return $this->post("content/faq/categories/{$categoryId}/items", $data);
    }

    /**
     * Atualiza um item de FAQ
     */
    public function updateFaqItem(int $categoryId, int $itemId, array $data): ?array
    {
        return $this->put("content/faq/categories/{$categoryId}/items/{$itemId}", $data);
    }

    /**
     * Exclui um item de FAQ
     */
    public function deleteFaqItem(int $categoryId, int $itemId): ?array
    {
        return $this->delete("content/faq/categories/{$categoryId}/items/{$itemId}");
    }

    // =====================================================================
    // Gestão de Conteúdo - Páginas
    // =====================================================================

    /**
     * Lista todas as páginas
     */
    public function getPages(array $filters = []): ?array
    {
        return $this->get('content/pages', $filters);
    }

    /**
     * Obtém uma página específica
     */
    public function getPage(int $id): ?array
    {
        return $this->get("content/pages/{$id}");
    }

    /**
     * Cria uma nova página
     */
    public function createPage(array $data): ?array
    {
        return $this->post('content/pages', $data);
    }

    /**
     * Atualiza uma página
     */
    public function updatePage(int $id, array $data): ?array
    {
        return $this->put("content/pages/{$id}", $data);
    }

    /**
     * Exclui uma página
     */
    public function deletePage(int $id): ?array
    {
        return $this->delete("content/pages/{$id}");
    }

    /**
     * Limpa cache de uma página
     */
    public function clearPageCache(?string $slug = null): ?array
    {
        return $this->post('webhooks/cache/pages/clear', ['slug' => $slug]);
    }
}
