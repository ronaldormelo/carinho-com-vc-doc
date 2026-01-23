<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\UtmLink;
use App\Models\Domain\DomainLandingStatus;
use App\Integrations\Internal\SiteClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servico de gestao de landing pages.
 */
class LandingPageService
{
    public function __construct(
        private SiteClient $site
    ) {}

    /**
     * Lista landing pages com filtros.
     */
    public function list(array $filters = []): array
    {
        $query = LandingPage::with(['status', 'utmDefault']);

        if (!empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('slug', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }

    /**
     * Obtem landing page por ID.
     */
    public function get(int $id): LandingPage
    {
        return LandingPage::with(['status', 'utmDefault'])->findOrFail($id);
    }

    /**
     * Obtem landing page por slug.
     */
    public function getBySlug(string $slug): ?LandingPage
    {
        return LandingPage::with(['status', 'utmDefault'])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Cria nova landing page.
     */
    public function create(array $data): LandingPage
    {
        return DB::transaction(function () use ($data) {
            // Gera slug unico
            $slug = $data['slug'] ?? LandingPage::generateUniqueSlug($data['name']);

            // Cria UTM padrao se fornecido
            $utmId = null;
            if (!empty($data['utm'])) {
                $utm = UtmLink::createFromParams(
                    $data['utm']['source'] ?? 'landing',
                    $data['utm']['medium'] ?? 'page',
                    $data['utm']['campaign'] ?? $slug,
                    $data['utm']['content'] ?? null,
                    $data['utm']['term'] ?? null
                );
                $utmId = $utm->id;
            }

            $landingPage = LandingPage::create([
                'slug' => $slug,
                'name' => $data['name'],
                'status_id' => DomainLandingStatus::DRAFT,
                'utm_default_id' => $utmId,
            ]);

            Log::info('Landing page created', ['id' => $landingPage->id, 'slug' => $slug]);

            return $landingPage->load(['status', 'utmDefault']);
        });
    }

    /**
     * Atualiza landing page.
     */
    public function update(int $id, array $data): LandingPage
    {
        $landingPage = LandingPage::findOrFail($id);

        if (!$landingPage->isEditable()) {
            throw new \Exception('Landing page nao pode ser editada.');
        }

        // Atualiza slug se fornecido e diferente
        if (!empty($data['slug']) && $data['slug'] !== $landingPage->slug) {
            $existingSlug = LandingPage::where('slug', $data['slug'])
                ->where('id', '!=', $id)
                ->exists();

            if ($existingSlug) {
                throw new \Exception('Slug ja existe.');
            }
        }

        $landingPage->update(array_filter([
            'slug' => $data['slug'] ?? null,
            'name' => $data['name'] ?? null,
        ], fn ($v) => $v !== null));

        // Atualiza no site se publicada
        if ($landingPage->isPublished()) {
            $this->site->updateLandingPage($landingPage->slug, [
                'name' => $landingPage->name,
            ]);
        }

        return $landingPage->fresh(['status', 'utmDefault']);
    }

    /**
     * Publica landing page.
     */
    public function publish(int $id): LandingPage
    {
        $landingPage = LandingPage::with('utmDefault')->findOrFail($id);

        if ($landingPage->isPublished()) {
            throw new \Exception('Landing page ja esta publicada.');
        }

        try {
            // Publica no site
            $result = $this->site->publishLandingPage([
                'slug' => $landingPage->slug,
                'name' => $landingPage->name,
                'utm' => $landingPage->utmDefault?->toArray(),
            ]);

            if (!($result['success'] ?? false)) {
                throw new \Exception('Erro ao publicar no site: ' . json_encode($result));
            }

            $landingPage->update(['status_id' => DomainLandingStatus::PUBLISHED]);

            Log::info('Landing page published', ['id' => $landingPage->id]);

        } catch (\Throwable $e) {
            Log::error('Landing page publish failed', [
                'id' => $landingPage->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $landingPage->fresh(['status', 'utmDefault']);
    }

    /**
     * Arquiva landing page.
     */
    public function archive(int $id): LandingPage
    {
        $landingPage = LandingPage::findOrFail($id);

        // Remove do site se publicada
        if ($landingPage->isPublished()) {
            $this->site->unpublishLandingPage($landingPage->slug);
        }

        $landingPage->update(['status_id' => DomainLandingStatus::ARCHIVED]);

        Log::info('Landing page archived', ['id' => $landingPage->id]);

        return $landingPage->fresh(['status', 'utmDefault']);
    }

    /**
     * Define UTM padrao.
     */
    public function setDefaultUtm(int $id, array $utmData): LandingPage
    {
        $landingPage = LandingPage::findOrFail($id);

        $utm = UtmLink::createFromParams(
            $utmData['source'],
            $utmData['medium'],
            $utmData['campaign'],
            $utmData['content'] ?? null,
            $utmData['term'] ?? null
        );

        $landingPage->update(['utm_default_id' => $utm->id]);

        return $landingPage->fresh(['status', 'utmDefault']);
    }

    /**
     * Obtem estatisticas da landing page.
     */
    public function getStats(int $id, string $startDate, string $endDate): array
    {
        $landingPage = LandingPage::findOrFail($id);

        // Busca estatisticas do site
        $result = $this->site->getLandingPageStats($landingPage->slug, $startDate, $endDate);

        return [
            'landing_page' => $landingPage->toArray(),
            'stats' => $result['data'] ?? [],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ];
    }

    /**
     * Gera URL completa com UTM.
     */
    public function generateUrl(int $id, ?array $customUtm = null): string
    {
        $landingPage = LandingPage::with('utmDefault')->findOrFail($id);

        if ($customUtm) {
            $params = http_build_query(array_filter([
                'utm_source' => $customUtm['source'] ?? null,
                'utm_medium' => $customUtm['medium'] ?? null,
                'utm_campaign' => $customUtm['campaign'] ?? null,
                'utm_content' => $customUtm['content'] ?? null,
                'utm_term' => $customUtm['term'] ?? null,
            ]));

            return $landingPage->url . '?' . $params;
        }

        return $landingPage->url_with_utm;
    }

    /**
     * Lista landing pages publicadas.
     */
    public function listPublished(): array
    {
        return LandingPage::with('utmDefault')
            ->published()
            ->orderBy('name')
            ->get()
            ->toArray();
    }
}
