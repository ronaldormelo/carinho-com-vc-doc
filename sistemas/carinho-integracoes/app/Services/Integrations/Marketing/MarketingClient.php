<?php

namespace App\Services\Integrations\Marketing;

use App\Services\Integrations\BaseClient;

/**
 * Cliente para integracao com o sistema de Marketing (marketing.carinho.com.vc).
 *
 * Responsavel por:
 * - Tracking de campanhas
 * - Atribuicao de leads
 * - Metricas de performance
 */
class MarketingClient extends BaseClient
{
    protected string $configKey = 'marketing';

    /*
    |--------------------------------------------------------------------------
    | Campanhas
    |--------------------------------------------------------------------------
    */

    /**
     * Registra conversao de campanha.
     */
    public function trackCampaignConversion(array $data): array
    {
        return $this->post('/api/v1/campaigns/conversions', $data);
    }

    /**
     * Busca campanha por UTM.
     */
    public function findCampaignByUtm(array $utm): array
    {
        return $this->get('/api/v1/campaigns/find', $utm);
    }

    /**
     * Atualiza metricas da campanha.
     */
    public function updateCampaignMetrics(int $campaignId, array $metrics): array
    {
        return $this->put("/api/v1/campaigns/{$campaignId}/metrics", $metrics);
    }

    /*
    |--------------------------------------------------------------------------
    | Leads
    |--------------------------------------------------------------------------
    */

    /**
     * Atribui lead a campanha.
     */
    public function attributeLeadToCampaign(int $leadId, int $campaignId): array
    {
        return $this->post('/api/v1/attribution', [
            'lead_id' => $leadId,
            'campaign_id' => $campaignId,
        ]);
    }

    /**
     * Busca origem do lead.
     */
    public function getLeadSource(int $leadId): array
    {
        return $this->get("/api/v1/leads/{$leadId}/source");
    }

    /*
    |--------------------------------------------------------------------------
    | Listas
    |--------------------------------------------------------------------------
    */

    /**
     * Adiciona contato a lista.
     */
    public function addToList(int $listId, array $contact): array
    {
        return $this->post("/api/v1/lists/{$listId}/contacts", $contact);
    }

    /**
     * Remove contato de lista.
     */
    public function removeFromList(int $listId, string $email): array
    {
        return $this->delete("/api/v1/lists/{$listId}/contacts/{$email}");
    }

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */

    /**
     * Dispara evento para Marketing.
     */
    public function dispatchEvent(string $eventType, array $payload): array
    {
        return $this->post('/api/v1/webhooks/events', [
            'event_type' => $eventType,
            'payload' => $payload,
            'source' => 'integracoes',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
