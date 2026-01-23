<?php

namespace App\Integrations\Internal;

use App\Integrations\BaseClient;

/**
 * Cliente para integracao com site principal.
 *
 * Responsavel por gerenciar landing pages e formularios no site.
 */
class SiteClient extends BaseClient
{
    public function __construct()
    {
        $this->baseUrl = config('integrations.site.base_url', 'https://carinho.com.vc/api');
        $this->timeout = (int) config('integrations.site.timeout', 8);
        $this->connectTimeout = 3;
        $this->cachePrefix = 'site';
    }

    /**
     * Publica landing page no site.
     */
    public function publishLandingPage(array $pageData): array
    {
        return $this->post('/landing-pages', $pageData);
    }

    /**
     * Atualiza landing page.
     */
    public function updateLandingPage(string $slug, array $pageData): array
    {
        return $this->put("/landing-pages/{$slug}", $pageData);
    }

    /**
     * Remove landing page.
     */
    public function unpublishLandingPage(string $slug): array
    {
        return $this->delete("/landing-pages/{$slug}");
    }

    /**
     * Obtem estatisticas de landing page.
     */
    public function getLandingPageStats(string $slug, string $startDate, string $endDate): array
    {
        return $this->get("/landing-pages/{$slug}/stats", [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Registra submissao de formulario.
     */
    public function registerFormSubmission(string $formId, array $data): array
    {
        return $this->post("/forms/{$formId}/submissions", $data);
    }

    /**
     * Obtem submissoes de formulario.
     */
    public function getFormSubmissions(string $formId, ?int $limit = 50): array
    {
        return $this->get("/forms/{$formId}/submissions", ['limit' => $limit]);
    }

    /**
     * Atualiza pixel de conversao no site.
     */
    public function updateConversionPixel(string $pageSlug, array $pixelData): array
    {
        return $this->put("/landing-pages/{$pageSlug}/pixel", $pixelData);
    }

    /**
     * Invalida cache de pagina.
     */
    public function invalidatePageCache(string $slug): array
    {
        return $this->post("/cache/invalidate", ['slug' => $slug]);
    }

    /**
     * Retorna headers padrao.
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . config('integrations.site.token'),
            'X-Internal-Token' => config('integrations.internal.token'),
        ];
    }
}
