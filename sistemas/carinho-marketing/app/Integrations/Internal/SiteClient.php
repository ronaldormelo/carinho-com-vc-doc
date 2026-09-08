<?php

namespace App\Integrations\Internal;

use App\Integrations\BaseClient;

/**
 * Landings ficam no próprio Marketing. No Site só há cache/CMS.
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

    public function publishLandingPage(array $pageData): array
    {
        return $this->notOnSite('POST /landing-pages');
    }

    public function updateLandingPage(string $slug, array $pageData): array
    {
        return $this->notOnSite("PUT /landing-pages/{$slug}");
    }

    public function unpublishLandingPage(string $slug): array
    {
        return $this->notOnSite("DELETE /landing-pages/{$slug}");
    }

    public function getLandingPageStats(string $slug, string $startDate, string $endDate): array
    {
        return $this->notOnSite("GET /landing-pages/{$slug}/stats");
    }

    public function registerFormSubmission(string $formId, array $data): array
    {
        return $this->notOnSite("POST /forms/{$formId}/submissions");
    }

    public function getFormSubmissions(string $formId, ?int $limit = 50): array
    {
        return $this->notOnSite("GET /forms/{$formId}/submissions");
    }

    public function updateConversionPixel(string $pageSlug, array $pixelData): array
    {
        return $this->notOnSite("PUT /landing-pages/{$pageSlug}/pixel");
    }

    public function invalidatePageCache(string $slug): array
    {
        return $this->post('/webhooks/cache/pages/clear', ['slug' => $slug]);
    }

    private function notOnSite(string $capability): array
    {
        return [
            'success' => false,
            'status' => 501,
            'data' => null,
            'error' => "Site não expõe {$capability}. Landings pertencem ao Marketing.",
        ];
    }

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
