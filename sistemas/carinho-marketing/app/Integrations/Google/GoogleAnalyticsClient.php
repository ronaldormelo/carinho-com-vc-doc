<?php

namespace App\Integrations\Google;

use App\Integrations\BaseClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para Google Analytics Data API e Measurement Protocol.
 *
 * Documentacao:
 * - Data API: https://developers.google.com/analytics/devguides/reporting/data/v1
 * - Measurement Protocol: https://developers.google.com/analytics/devguides/collection/protocol/ga4
 */
class GoogleAnalyticsClient extends BaseClient
{
    private string $measurementId;
    private string $apiSecret;
    private string $propertyId;

    public function __construct()
    {
        $this->baseUrl = 'https://analyticsdata.googleapis.com';
        $this->measurementId = config('integrations.google_analytics.measurement_id', '');
        $this->apiSecret = config('integrations.google_analytics.api_secret', '');
        $this->propertyId = config('integrations.google_analytics.property_id', '');
        $this->timeout = 30;
        $this->connectTimeout = 5;
        $this->cachePrefix = 'google_analytics';
    }

    /**
     * Envia evento via Measurement Protocol (GA4).
     */
    public function sendEvent(
        string $clientId,
        string $eventName,
        array $params = [],
        ?string $userId = null
    ): array {
        $url = "https://www.google-analytics.com/mp/collect";
        $url .= "?measurement_id={$this->measurementId}&api_secret={$this->apiSecret}";

        $payload = [
            'client_id' => $clientId,
            'events' => [
                [
                    'name' => $eventName,
                    'params' => $params,
                ],
            ],
        ];

        if ($userId) {
            $payload['user_id'] = $userId;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->post($url, $payload);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::error('Google Analytics send event error', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envia evento de geracao de lead.
     */
    public function sendLeadEvent(string $clientId, array $leadData = [], ?string $userId = null): array
    {
        return $this->sendEvent($clientId, 'generate_lead', array_filter([
            'currency' => 'BRL',
            'value' => $leadData['value'] ?? null,
            'lead_source' => $leadData['source'] ?? null,
            'campaign' => $leadData['campaign'] ?? null,
        ]), $userId);
    }

    /**
     * Envia evento de contato.
     */
    public function sendContactEvent(string $clientId, array $contactData = [], ?string $userId = null): array
    {
        return $this->sendEvent($clientId, 'contact', array_filter([
            'method' => $contactData['method'] ?? 'whatsapp',
            'source' => $contactData['source'] ?? null,
        ]), $userId);
    }

    /**
     * Envia evento de visualizacao de conteudo.
     */
    public function sendViewContentEvent(
        string $clientId,
        string $contentName,
        ?string $contentCategory = null,
        ?string $userId = null
    ): array {
        return $this->sendEvent($clientId, 'view_item', [
            'items' => [
                [
                    'item_name' => $contentName,
                    'item_category' => $contentCategory,
                ],
            ],
        ], $userId);
    }

    /**
     * Obtem relatorio de metricas.
     * Requer configuracao de service account.
     */
    public function runReport(
        array $dimensions,
        array $metrics,
        string $startDate,
        string $endDate,
        ?array $dimensionFilter = null
    ): array {
        $payload = [
            'dateRanges' => [
                [
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ],
            ],
            'dimensions' => array_map(fn ($d) => ['name' => $d], $dimensions),
            'metrics' => array_map(fn ($m) => ['name' => $m], $metrics),
        ];

        if ($dimensionFilter) {
            $payload['dimensionFilter'] = $dimensionFilter;
        }

        // Esta funcao requer autenticacao OAuth2 com service account
        // Para implementacao completa, use google/apiclient ou configure JWT
        return $this->post(
            "v1beta/properties/{$this->propertyId}:runReport",
            $payload
        );
    }

    /**
     * Obtem metricas de sessoes por canal.
     */
    public function getSessionsByChannel(string $startDate, string $endDate): array
    {
        return $this->runReport(
            ['sessionDefaultChannelGroup'],
            ['sessions', 'totalUsers', 'bounceRate'],
            $startDate,
            $endDate
        );
    }

    /**
     * Obtem metricas de conversao por campanha.
     */
    public function getConversionsByCampaign(string $startDate, string $endDate): array
    {
        return $this->runReport(
            ['sessionCampaignName', 'sessionSource', 'sessionMedium'],
            ['sessions', 'conversions', 'totalUsers'],
            $startDate,
            $endDate
        );
    }

    /**
     * Obtem metricas de landing pages.
     */
    public function getLandingPageMetrics(string $startDate, string $endDate): array
    {
        return $this->runReport(
            ['landingPage'],
            ['sessions', 'totalUsers', 'bounceRate', 'averageSessionDuration'],
            $startDate,
            $endDate
        );
    }

    /**
     * Gera client ID para Measurement Protocol.
     */
    public static function generateClientId(): string
    {
        return sprintf(
            '%d.%d',
            random_int(1000000000, 9999999999),
            time()
        );
    }

    /**
     * Eventos padrao GA4.
     */
    public static function getStandardEvents(): array
    {
        return [
            'page_view' => 'Visualizacao de pagina',
            'generate_lead' => 'Geracao de lead',
            'contact' => 'Contato',
            'sign_up' => 'Cadastro',
            'login' => 'Login',
            'view_item' => 'Visualizacao de item',
            'begin_checkout' => 'Inicio de checkout',
            'purchase' => 'Compra',
        ];
    }
}
