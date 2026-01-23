<?php

namespace App\Integrations\Internal;

use App\Integrations\BaseClient;

/**
 * Cliente para integracao com hub de Integracoes.
 *
 * Responsavel por comunicar eventos e automacoes entre sistemas.
 */
class IntegracoesClient extends BaseClient
{
    public function __construct()
    {
        $this->baseUrl = config('integrations.integracoes.base_url', 'https://integracoes.carinho.com.vc/api');
        $this->timeout = (int) config('integrations.integracoes.timeout', 8);
        $this->connectTimeout = 3;
        $this->cachePrefix = 'integracoes';
    }

    /**
     * Dispara evento de marketing.
     */
    public function dispatchEvent(string $eventType, array $eventData): array
    {
        return $this->post('/events', [
            'type' => $eventType,
            'source' => 'marketing',
            'data' => $eventData,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Dispara evento de novo lead.
     */
    public function dispatchLeadCreated(array $leadData, array $sourceData = []): array
    {
        return $this->dispatchEvent('lead.created', [
            'lead' => $leadData,
            'source' => $sourceData,
        ]);
    }

    /**
     * Dispara evento de conversao.
     */
    public function dispatchConversion(string $conversionType, array $conversionData): array
    {
        return $this->dispatchEvent('conversion.registered', [
            'type' => $conversionType,
            'data' => $conversionData,
        ]);
    }

    /**
     * Dispara evento de campanha ativada.
     */
    public function dispatchCampaignActivated(int $campaignId, array $campaignData): array
    {
        return $this->dispatchEvent('campaign.activated', [
            'campaign_id' => $campaignId,
            'campaign' => $campaignData,
        ]);
    }

    /**
     * Dispara evento de conteudo publicado.
     */
    public function dispatchContentPublished(int $contentId, string $channel, array $contentData): array
    {
        return $this->dispatchEvent('content.published', [
            'content_id' => $contentId,
            'channel' => $channel,
            'content' => $contentData,
        ]);
    }

    /**
     * Solicita sincronizacao de metricas.
     */
    public function requestMetricsSync(string $platform, ?string $startDate = null, ?string $endDate = null): array
    {
        return $this->post('/sync/request', [
            'type' => 'metrics',
            'platform' => $platform,
            'start_date' => $startDate ?? now()->subDays(7)->toDateString(),
            'end_date' => $endDate ?? now()->toDateString(),
        ]);
    }

    /**
     * Envia notificacao para canal.
     */
    public function sendNotification(string $channel, string $type, array $data): array
    {
        return $this->post('/notifications', [
            'channel' => $channel,
            'type' => $type,
            'data' => $data,
        ]);
    }

    /**
     * Envia mensagem WhatsApp via hub.
     */
    public function sendWhatsAppMessage(string $phone, string $message, ?string $mediaUrl = null): array
    {
        return $this->post('/whatsapp/send', [
            'phone' => $phone,
            'message' => $message,
            'media_url' => $mediaUrl,
        ]);
    }

    /**
     * Retorna headers padrao.
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . config('integrations.integracoes.token'),
            'X-Internal-Token' => config('integrations.internal.token'),
        ];
    }
}
