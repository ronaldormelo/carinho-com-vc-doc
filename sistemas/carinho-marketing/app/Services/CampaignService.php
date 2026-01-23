<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\AdGroup;
use App\Models\Creative;
use App\Models\CampaignMetric;
use App\Models\Domain\DomainCampaignStatus;
use App\Integrations\Meta\MetaAdsClient;
use App\Integrations\Google\GoogleAdsClient;
use App\Integrations\Internal\IntegracoesClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Servico de gestao de campanhas de marketing.
 */
class CampaignService
{
    public function __construct(
        private MetaAdsClient $metaAds,
        private GoogleAdsClient $googleAds,
        private IntegracoesClient $integracoes
    ) {}

    /**
     * Lista campanhas com filtros.
     */
    public function list(array $filters = []): array
    {
        $query = Campaign::with(['channel', 'status']);

        if (!empty($filters['channel_id'])) {
            $query->byChannel($filters['channel_id']);
        }

        if (!empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->inPeriod($filters['start_date'], $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }

    /**
     * Obtem campanha com detalhes.
     */
    public function get(int $id): array
    {
        $campaign = Campaign::with(['channel', 'status', 'adGroups.creatives'])
            ->findOrFail($id);

        $metrics = CampaignMetric::aggregateByCampaign($id);

        return [
            'campaign' => $campaign->toArray(),
            'metrics' => $metrics,
        ];
    }

    /**
     * Cria nova campanha.
     */
    public function create(array $data): Campaign
    {
        return DB::transaction(function () use ($data) {
            $campaign = Campaign::create([
                'channel_id' => $data['channel_id'],
                'name' => $data['name'],
                'objective' => $data['objective'],
                'budget' => $data['budget'] ?? 0,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status_id' => DomainCampaignStatus::PLANNED,
            ]);

            Log::info('Campaign created', ['id' => $campaign->id]);

            return $campaign->load(['channel', 'status']);
        });
    }

    /**
     * Atualiza campanha.
     */
    public function update(int $id, array $data): Campaign
    {
        $campaign = Campaign::findOrFail($id);

        $campaign->update(array_filter([
            'name' => $data['name'] ?? null,
            'objective' => $data['objective'] ?? null,
            'budget' => $data['budget'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ], fn ($v) => $v !== null));

        return $campaign->fresh(['channel', 'status']);
    }

    /**
     * Ativa campanha.
     */
    public function activate(int $id): Campaign
    {
        $campaign = Campaign::with('channel')->findOrFail($id);

        if (!$campaign->canBeActivated()) {
            throw new \Exception('Campanha nao pode ser ativada.');
        }

        // Verifica se tem grupos e criativos
        if ($campaign->adGroups()->count() === 0) {
            throw new \Exception('Campanha precisa ter pelo menos um grupo de anuncios.');
        }

        $campaign->update(['status_id' => DomainCampaignStatus::ACTIVE]);

        // Dispara evento
        $this->integracoes->dispatchCampaignActivated($campaign->id, $campaign->toArray());

        Log::info('Campaign activated', ['id' => $campaign->id]);

        return $campaign->fresh(['channel', 'status']);
    }

    /**
     * Pausa campanha.
     */
    public function pause(int $id): Campaign
    {
        $campaign = Campaign::findOrFail($id);

        if (!$campaign->isActive()) {
            throw new \Exception('Campanha nao esta ativa.');
        }

        $campaign->update(['status_id' => DomainCampaignStatus::PAUSED]);

        Log::info('Campaign paused', ['id' => $campaign->id]);

        return $campaign->fresh(['channel', 'status']);
    }

    /**
     * Finaliza campanha.
     */
    public function finish(int $id): Campaign
    {
        $campaign = Campaign::findOrFail($id);

        $campaign->update(['status_id' => DomainCampaignStatus::FINISHED]);

        Log::info('Campaign finished', ['id' => $campaign->id]);

        return $campaign->fresh(['channel', 'status']);
    }

    /**
     * Adiciona grupo de anuncios.
     */
    public function addAdGroup(int $campaignId, array $data): AdGroup
    {
        $campaign = Campaign::findOrFail($campaignId);

        return $campaign->adGroups()->create([
            'name' => $data['name'],
            'targeting_json' => $data['targeting'] ?? [],
        ]);
    }

    /**
     * Atualiza grupo de anuncios.
     */
    public function updateAdGroup(int $adGroupId, array $data): AdGroup
    {
        $adGroup = AdGroup::findOrFail($adGroupId);

        $adGroup->update(array_filter([
            'name' => $data['name'] ?? null,
            'targeting_json' => $data['targeting'] ?? null,
        ], fn ($v) => $v !== null));

        return $adGroup->fresh();
    }

    /**
     * Adiciona criativo ao grupo.
     */
    public function addCreative(int $adGroupId, array $data): Creative
    {
        $adGroup = AdGroup::findOrFail($adGroupId);

        return $adGroup->creatives()->create([
            'creative_type_id' => $data['type_id'],
            'headline' => $data['headline'],
            'body' => $data['body'],
            'media_url' => $data['media_url'] ?? null,
        ]);
    }

    /**
     * Sincroniza metricas da plataforma.
     */
    public function syncMetrics(int $campaignId, string $startDate, string $endDate): array
    {
        $campaign = Campaign::with('channel')->findOrFail($campaignId);
        $channelName = strtolower($campaign->channel->name ?? '');

        $metrics = [];

        try {
            if (str_contains($channelName, 'meta') || str_contains($channelName, 'facebook')) {
                $metrics = $this->syncMetaMetrics($campaign, $startDate, $endDate);
            } elseif (str_contains($channelName, 'google')) {
                $metrics = $this->syncGoogleMetrics($campaign, $startDate, $endDate);
            }

            Log::info('Campaign metrics synced', [
                'campaign_id' => $campaignId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

        } catch (\Throwable $e) {
            Log::error('Campaign metrics sync failed', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $metrics;
    }

    /**
     * Sincroniza metricas do Meta Ads.
     */
    private function syncMetaMetrics(Campaign $campaign, string $startDate, string $endDate): array
    {
        // Assumindo que temos um external_id no JSON de metadata
        $externalId = $campaign->external_id ?? null;

        if (!$externalId) {
            return [];
        }

        $result = $this->metaAds->getCampaignInsights(
            $externalId,
            null,
            $startDate,
            $endDate
        );

        if (!$result['success'] || empty($result['data']['data'])) {
            return [];
        }

        $metrics = [];
        foreach ($result['data']['data'] as $dayData) {
            $date = $dayData['date_start'] ?? $startDate;

            $metric = CampaignMetric::upsert($campaign->id, $date, [
                'impressions' => (int) ($dayData['impressions'] ?? 0),
                'clicks' => (int) ($dayData['clicks'] ?? 0),
                'spend' => (float) ($dayData['spend'] ?? 0),
                'leads' => $this->extractLeadsFromActions($dayData['actions'] ?? []),
            ]);

            $metrics[] = $metric;
        }

        return $metrics;
    }

    /**
     * Sincroniza metricas do Google Ads.
     */
    private function syncGoogleMetrics(Campaign $campaign, string $startDate, string $endDate): array
    {
        $externalId = $campaign->external_id ?? null;

        if (!$externalId) {
            return [];
        }

        $result = $this->googleAds->getCampaignMetrics($externalId, $startDate, $endDate);

        if (!$result['success'] || empty($result['data']['results'])) {
            return [];
        }

        $metrics = [];
        foreach ($result['data']['results'] as $row) {
            $date = $row['segments']['date'] ?? $startDate;

            $metric = CampaignMetric::upsert($campaign->id, $date, [
                'impressions' => (int) ($row['metrics']['impressions'] ?? 0),
                'clicks' => (int) ($row['metrics']['clicks'] ?? 0),
                'spend' => GoogleAdsClient::microsToAmount((int) ($row['metrics']['cost_micros'] ?? 0)),
                'leads' => (int) ($row['metrics']['conversions'] ?? 0),
            ]);

            $metrics[] = $metric;
        }

        return $metrics;
    }

    /**
     * Extrai leads das acoes do Meta.
     */
    private function extractLeadsFromActions(array $actions): int
    {
        foreach ($actions as $action) {
            if (in_array($action['action_type'] ?? '', ['lead', 'contact', 'complete_registration'])) {
                return (int) ($action['value'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * Obtem metricas agregadas de uma campanha.
     */
    public function getMetrics(int $campaignId, ?string $startDate = null, ?string $endDate = null): array
    {
        return CampaignMetric::aggregateByCampaign($campaignId, $startDate, $endDate);
    }

    /**
     * Obtem metricas diarias.
     */
    public function getDailyMetrics(int $campaignId, string $startDate, string $endDate): array
    {
        return CampaignMetric::where('campaign_id', $campaignId)
            ->inPeriod($startDate, $endDate)
            ->orderBy('metric_date')
            ->get()
            ->toArray();
    }

    /**
     * Dashboard de campanhas.
     */
    public function getDashboard(): array
    {
        $cacheKey = 'campaign_dashboard_' . now()->format('Y-m-d-H');

        return Cache::remember($cacheKey, 3600, function () {
            $activeCampaigns = Campaign::active()->with('channel')->get();

            $totalMetrics = CampaignMetric::thisMonth()
                ->selectRaw('
                    SUM(impressions) as total_impressions,
                    SUM(clicks) as total_clicks,
                    SUM(spend) as total_spend,
                    SUM(leads) as total_leads
                ')
                ->first();

            return [
                'active_campaigns' => $activeCampaigns->count(),
                'campaigns' => $activeCampaigns->toArray(),
                'month_metrics' => [
                    'impressions' => (int) ($totalMetrics->total_impressions ?? 0),
                    'clicks' => (int) ($totalMetrics->total_clicks ?? 0),
                    'spend' => (float) ($totalMetrics->total_spend ?? 0),
                    'leads' => (int) ($totalMetrics->total_leads ?? 0),
                ],
                'performance' => $this->calculatePerformanceIndicators($totalMetrics),
            ];
        });
    }

    /**
     * Calcula indicadores de performance.
     */
    private function calculatePerformanceIndicators($metrics): array
    {
        $impressions = (int) ($metrics->total_impressions ?? 0);
        $clicks = (int) ($metrics->total_clicks ?? 0);
        $spend = (float) ($metrics->total_spend ?? 0);
        $leads = (int) ($metrics->total_leads ?? 0);

        return [
            'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : null,
            'cpc' => $clicks > 0 ? round($spend / $clicks, 2) : null,
            'cpl' => $leads > 0 ? round($spend / $leads, 2) : null,
            'conversion_rate' => $clicks > 0 ? round(($leads / $clicks) * 100, 2) : null,
        ];
    }
}
