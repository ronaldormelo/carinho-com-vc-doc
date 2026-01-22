<?php

namespace App\Integrations\Meta;

use App\Integrations\BaseClient;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para Meta Marketing API (Facebook/Instagram Ads).
 *
 * Documentacao: https://developers.facebook.com/docs/marketing-api/
 *
 * Endpoints principais:
 * - GET /{ad-account-id}/campaigns - Lista campanhas
 * - POST /{ad-account-id}/campaigns - Cria campanha
 * - GET /{campaign-id}/insights - Metricas da campanha
 * - POST /{ad-account-id}/adsets - Cria conjunto de anuncios
 * - POST /{ad-account-id}/ads - Cria anuncio
 * - POST /{ad-account-id}/adcreatives - Cria criativo
 */
class MetaAdsClient extends BaseClient
{
    private string $accessToken;
    private string $adAccountId;
    private string $apiVersion;

    public function __construct()
    {
        $this->baseUrl = config('integrations.meta.base_url', 'https://graph.facebook.com');
        $this->apiVersion = config('integrations.meta.api_version', 'v18.0');
        $this->accessToken = config('integrations.meta.access_token', '');
        $this->adAccountId = config('integrations.meta.ad_account_id', '');
        $this->timeout = (int) config('integrations.meta.timeout', 30);
        $this->connectTimeout = (int) config('integrations.meta.connect_timeout', 5);
        $this->cachePrefix = 'meta_ads';
    }

    /**
     * Lista todas as campanhas da conta.
     */
    public function listCampaigns(array $fields = [], ?string $status = null): array
    {
        $defaultFields = [
            'id',
            'name',
            'status',
            'objective',
            'daily_budget',
            'lifetime_budget',
            'start_time',
            'stop_time',
            'created_time',
            'updated_time',
        ];

        $params = [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $fields ?: $defaultFields),
        ];

        if ($status) {
            $params['effective_status'] = [$status];
        }

        return $this->get("act_{$this->adAccountId}/campaigns", $params);
    }

    /**
     * Obtem detalhes de uma campanha.
     */
    public function getCampaign(string $campaignId, array $fields = []): array
    {
        $defaultFields = [
            'id',
            'name',
            'status',
            'objective',
            'daily_budget',
            'lifetime_budget',
            'start_time',
            'stop_time',
            'spend_cap',
        ];

        $params = [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $fields ?: $defaultFields),
        ];

        return $this->get($campaignId, $params);
    }

    /**
     * Cria uma nova campanha.
     */
    public function createCampaign(array $data): array
    {
        $payload = array_merge([
            'access_token' => $this->accessToken,
            'status' => 'PAUSED', // Sempre inicia pausada
            'special_ad_categories' => [], // Sem categorias especiais
        ], $data);

        return $this->post("act_{$this->adAccountId}/campaigns", $payload);
    }

    /**
     * Atualiza uma campanha existente.
     */
    public function updateCampaign(string $campaignId, array $data): array
    {
        $payload = array_merge([
            'access_token' => $this->accessToken,
        ], $data);

        return $this->post($campaignId, $payload);
    }

    /**
     * Pausa uma campanha.
     */
    public function pauseCampaign(string $campaignId): array
    {
        return $this->updateCampaign($campaignId, ['status' => 'PAUSED']);
    }

    /**
     * Ativa uma campanha.
     */
    public function activateCampaign(string $campaignId): array
    {
        return $this->updateCampaign($campaignId, ['status' => 'ACTIVE']);
    }

    /**
     * Obtem insights (metricas) de uma campanha.
     */
    public function getCampaignInsights(
        string $campaignId,
        ?string $datePreset = null,
        ?string $startDate = null,
        ?string $endDate = null,
        array $fields = []
    ): array {
        $defaultFields = [
            'impressions',
            'clicks',
            'spend',
            'reach',
            'cpm',
            'cpc',
            'ctr',
            'actions',
            'cost_per_action_type',
        ];

        $params = [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $fields ?: $defaultFields),
        ];

        if ($datePreset) {
            $params['date_preset'] = $datePreset;
        } elseif ($startDate && $endDate) {
            $params['time_range'] = json_encode([
                'since' => $startDate,
                'until' => $endDate,
            ]);
        }

        return $this->get("{$campaignId}/insights", $params);
    }

    /**
     * Lista conjuntos de anuncios de uma campanha.
     */
    public function listAdSets(string $campaignId, array $fields = []): array
    {
        $defaultFields = [
            'id',
            'name',
            'status',
            'targeting',
            'daily_budget',
            'lifetime_budget',
            'start_time',
            'end_time',
        ];

        $params = [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $fields ?: $defaultFields),
        ];

        return $this->get("{$campaignId}/adsets", $params);
    }

    /**
     * Cria um conjunto de anuncios.
     */
    public function createAdSet(array $data): array
    {
        $payload = array_merge([
            'access_token' => $this->accessToken,
            'status' => 'PAUSED',
            'billing_event' => 'IMPRESSIONS',
            'optimization_goal' => 'REACH',
        ], $data);

        return $this->post("act_{$this->adAccountId}/adsets", $payload);
    }

    /**
     * Lista anuncios de um conjunto.
     */
    public function listAds(string $adSetId, array $fields = []): array
    {
        $defaultFields = [
            'id',
            'name',
            'status',
            'creative',
            'effective_status',
        ];

        $params = [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $fields ?: $defaultFields),
        ];

        return $this->get("{$adSetId}/ads", $params);
    }

    /**
     * Cria um anuncio.
     */
    public function createAd(array $data): array
    {
        $payload = array_merge([
            'access_token' => $this->accessToken,
            'status' => 'PAUSED',
        ], $data);

        return $this->post("act_{$this->adAccountId}/ads", $payload);
    }

    /**
     * Cria um criativo.
     */
    public function createCreative(array $data): array
    {
        $payload = array_merge([
            'access_token' => $this->accessToken,
        ], $data);

        return $this->post("act_{$this->adAccountId}/adcreatives", $payload);
    }

    /**
     * Obtem audiencias personalizadas.
     */
    public function listCustomAudiences(): array
    {
        $params = [
            'access_token' => $this->accessToken,
            'fields' => 'id,name,subtype,approximate_count,data_source',
        ];

        return $this->get("act_{$this->adAccountId}/customaudiences", $params);
    }

    /**
     * Constroi URL com versao da API.
     */
    protected function buildUrl(string $endpoint): string
    {
        return rtrim($this->baseUrl, '/') . '/' . $this->apiVersion . '/' . ltrim($endpoint, '/');
    }

    /**
     * Objetivos de campanha disponiveis.
     */
    public static function getObjectives(): array
    {
        return [
            'OUTCOME_AWARENESS' => 'Reconhecimento',
            'OUTCOME_ENGAGEMENT' => 'Engajamento',
            'OUTCOME_LEADS' => 'Geracao de Leads',
            'OUTCOME_TRAFFIC' => 'Trafego',
            'OUTCOME_APP_PROMOTION' => 'Promocao de App',
            'OUTCOME_SALES' => 'Vendas',
        ];
    }

    /**
     * Status de campanha disponiveis.
     */
    public static function getStatuses(): array
    {
        return [
            'ACTIVE' => 'Ativa',
            'PAUSED' => 'Pausada',
            'DELETED' => 'Excluida',
            'ARCHIVED' => 'Arquivada',
        ];
    }
}
