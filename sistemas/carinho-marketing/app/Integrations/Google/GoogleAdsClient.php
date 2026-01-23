<?php

namespace App\Integrations\Google;

use App\Integrations\BaseClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Cliente para Google Ads API.
 *
 * Documentacao: https://developers.google.com/google-ads/api/docs/start
 *
 * Recursos principais:
 * - customers/{customer_id}/campaigns - Campanhas
 * - customers/{customer_id}/adGroups - Grupos de anuncios
 * - customers/{customer_id}/ads - Anuncios
 * - customers/{customer_id}/conversionActions - Acoes de conversao
 *
 * Esta e uma implementacao simplificada usando REST API.
 * Para producao completa, considere usar a biblioteca oficial google-ads-php.
 */
class GoogleAdsClient extends BaseClient
{
    private string $developerToken;
    private string $customerId;
    private string $loginCustomerId;
    private string $accessToken;
    private string $apiVersion;

    public function __construct()
    {
        $this->baseUrl = 'https://googleads.googleapis.com';
        $this->apiVersion = config('integrations.google_ads.api_version', 'v15');
        $this->developerToken = config('integrations.google_ads.developer_token', '');
        $this->customerId = str_replace('-', '', config('integrations.google_ads.customer_id', ''));
        $this->loginCustomerId = str_replace('-', '', config('integrations.google_ads.login_customer_id', ''));
        $this->timeout = (int) config('integrations.google_ads.timeout', 30);
        $this->connectTimeout = (int) config('integrations.google_ads.connect_timeout', 5);
        $this->cachePrefix = 'google_ads';
    }

    /**
     * Obtem access token usando refresh token.
     */
    private function getAccessToken(): string
    {
        if (!empty($this->accessToken)) {
            return $this->accessToken;
        }

        $cacheKey = 'google_ads_access_token';
        $cached = $this->cache($cacheKey, function () {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('integrations.google_ads.client_id'),
                'client_secret' => config('integrations.google_ads.client_secret'),
                'refresh_token' => config('integrations.google_ads.refresh_token'),
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('Google Ads: Failed to get access token', [
                'response' => $response->body(),
            ]);

            return '';
        }, 3500); // Token expira em 1h, cache por ~58min

        $this->accessToken = $cached;
        return $this->accessToken;
    }

    /**
     * Lista campanhas.
     */
    public function listCampaigns(?string $status = null): array
    {
        $query = "SELECT 
            campaign.id,
            campaign.name,
            campaign.status,
            campaign.advertising_channel_type,
            campaign.start_date,
            campaign.end_date,
            campaign_budget.amount_micros
            FROM campaign";

        if ($status) {
            $query .= " WHERE campaign.status = '{$status}'";
        }

        return $this->search($query);
    }

    /**
     * Obtem detalhes de uma campanha.
     */
    public function getCampaign(string $campaignId): array
    {
        $query = "SELECT 
            campaign.id,
            campaign.name,
            campaign.status,
            campaign.advertising_channel_type,
            campaign.start_date,
            campaign.end_date,
            campaign_budget.amount_micros,
            metrics.impressions,
            metrics.clicks,
            metrics.cost_micros,
            metrics.conversions
            FROM campaign
            WHERE campaign.id = {$campaignId}";

        return $this->search($query);
    }

    /**
     * Obtem metricas de campanhas por periodo.
     */
    public function getCampaignMetrics(string $campaignId, string $startDate, string $endDate): array
    {
        $query = "SELECT 
            campaign.id,
            campaign.name,
            segments.date,
            metrics.impressions,
            metrics.clicks,
            metrics.cost_micros,
            metrics.conversions,
            metrics.average_cpc,
            metrics.ctr
            FROM campaign
            WHERE campaign.id = {$campaignId}
            AND segments.date BETWEEN '{$startDate}' AND '{$endDate}'
            ORDER BY segments.date";

        return $this->search($query);
    }

    /**
     * Lista grupos de anuncios de uma campanha.
     */
    public function listAdGroups(string $campaignId): array
    {
        $query = "SELECT 
            ad_group.id,
            ad_group.name,
            ad_group.status,
            ad_group.type
            FROM ad_group
            WHERE campaign.id = {$campaignId}";

        return $this->search($query);
    }

    /**
     * Lista anuncios de um grupo.
     */
    public function listAds(string $adGroupId): array
    {
        $query = "SELECT 
            ad_group_ad.ad.id,
            ad_group_ad.ad.name,
            ad_group_ad.status,
            ad_group_ad.ad.type,
            ad_group_ad.ad.final_urls
            FROM ad_group_ad
            WHERE ad_group.id = {$adGroupId}";

        return $this->search($query);
    }

    /**
     * Lista acoes de conversao.
     */
    public function listConversionActions(): array
    {
        $query = "SELECT 
            conversion_action.id,
            conversion_action.name,
            conversion_action.type,
            conversion_action.status,
            conversion_action.category
            FROM conversion_action";

        return $this->search($query);
    }

    /**
     * Cria acao de conversao.
     */
    public function createConversionAction(string $name, string $category = 'LEAD'): array
    {
        $operation = [
            'create' => [
                'name' => $name,
                'type' => 'WEBPAGE',
                'category' => $category,
                'status' => 'ENABLED',
                'countingType' => 'ONE_PER_CLICK',
                'viewThroughLookbackWindowDays' => 1,
                'clickThroughLookbackWindowDays' => 30,
            ],
        ];

        return $this->mutate('conversionActions', [$operation]);
    }

    /**
     * Envia conversao offline.
     */
    public function uploadOfflineConversion(
        string $conversionActionId,
        string $gclid,
        string $conversionDateTime,
        ?float $conversionValue = null,
        ?string $currencyCode = 'BRL'
    ): array {
        $conversion = [
            'conversionAction' => "customers/{$this->customerId}/conversionActions/{$conversionActionId}",
            'gclid' => $gclid,
            'conversionDateTime' => $conversionDateTime,
        ];

        if ($conversionValue !== null) {
            $conversion['conversionValue'] = $conversionValue;
            $conversion['currencyCode'] = $currencyCode;
        }

        $payload = [
            'conversions' => [$conversion],
            'partialFailure' => true,
        ];

        return $this->post(
            "{$this->apiVersion}/customers/{$this->customerId}:uploadClickConversions",
            $payload
        );
    }

    /**
     * Envia conversao com dados de usuario (Enhanced Conversions).
     */
    public function uploadEnhancedConversion(
        string $conversionActionId,
        string $conversionDateTime,
        array $userData,
        ?float $conversionValue = null
    ): array {
        $userIdentifiers = [];

        if (isset($userData['email'])) {
            $userIdentifiers[] = [
                'hashedEmail' => hash('sha256', strtolower(trim($userData['email']))),
            ];
        }

        if (isset($userData['phone'])) {
            $phone = preg_replace('/\D/', '', $userData['phone']);
            if (!str_starts_with($phone, '+55')) {
                $phone = '+55' . $phone;
            }
            $userIdentifiers[] = [
                'hashedPhoneNumber' => hash('sha256', $phone),
            ];
        }

        if (isset($userData['first_name']) && isset($userData['last_name'])) {
            $userIdentifiers[] = [
                'addressInfo' => [
                    'hashedFirstName' => hash('sha256', strtolower(trim($userData['first_name']))),
                    'hashedLastName' => hash('sha256', strtolower(trim($userData['last_name']))),
                    'countryCode' => 'BR',
                ],
            ];
        }

        $conversion = [
            'conversionAction' => "customers/{$this->customerId}/conversionActions/{$conversionActionId}",
            'conversionDateTime' => $conversionDateTime,
            'userIdentifiers' => $userIdentifiers,
        ];

        if ($conversionValue !== null) {
            $conversion['conversionValue'] = $conversionValue;
            $conversion['currencyCode'] = 'BRL';
        }

        $payload = [
            'conversions' => [$conversion],
            'partialFailure' => true,
        ];

        return $this->post(
            "{$this->apiVersion}/customers/{$this->customerId}:uploadClickConversions",
            $payload
        );
    }

    /**
     * Executa query GAQL (Google Ads Query Language).
     */
    public function search(string $query): array
    {
        $payload = [
            'query' => $query,
        ];

        return $this->post(
            "{$this->apiVersion}/customers/{$this->customerId}/googleAds:search",
            $payload
        );
    }

    /**
     * Executa mutacao (create/update/delete).
     */
    public function mutate(string $resource, array $operations): array
    {
        $payload = [
            'operations' => $operations,
            'partialFailure' => true,
        ];

        return $this->post(
            "{$this->apiVersion}/customers/{$this->customerId}/{$resource}:mutate",
            $payload
        );
    }

    /**
     * Retorna headers padrao.
     */
    protected function getDefaultHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'developer-token' => $this->developerToken,
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ];

        if ($this->loginCustomerId) {
            $headers['login-customer-id'] = $this->loginCustomerId;
        }

        return $headers;
    }

    /**
     * Status de campanha disponiveis.
     */
    public static function getCampaignStatuses(): array
    {
        return [
            'ENABLED' => 'Ativa',
            'PAUSED' => 'Pausada',
            'REMOVED' => 'Removida',
        ];
    }

    /**
     * Tipos de campanha disponiveis.
     */
    public static function getCampaignTypes(): array
    {
        return [
            'SEARCH' => 'Pesquisa',
            'DISPLAY' => 'Display',
            'VIDEO' => 'Video',
            'SHOPPING' => 'Shopping',
            'PERFORMANCE_MAX' => 'Performance Max',
        ];
    }

    /**
     * Converte micros para valor real.
     */
    public static function microsToAmount(int $micros): float
    {
        return $micros / 1000000;
    }

    /**
     * Converte valor real para micros.
     */
    public static function amountToMicros(float $amount): int
    {
        return (int) ($amount * 1000000);
    }
}
