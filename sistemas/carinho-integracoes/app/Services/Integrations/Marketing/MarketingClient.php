<?php

namespace App\Services\Integrations\Marketing;

use App\Services\Integrations\BaseClient;

/**
 * Cliente de Marketing. Rotas reais: /api/campaigns, /api/conversions, /api/utm (sem /v1).
 */
class MarketingClient extends BaseClient
{
    protected string $configKey = 'marketing';

    public function trackCampaignConversion(array $data): array
    {
        return $this->post('/api/conversions/lead', $data);
    }

    public function findCampaignByUtm(array $utm): array
    {
        $query = array_filter([
            'source' => $utm['utm_source'] ?? $utm['source'] ?? null,
            'medium' => $utm['utm_medium'] ?? $utm['medium'] ?? null,
            'campaign' => $utm['utm_campaign'] ?? $utm['campaign'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        if ($query === []) {
            return $this->unsupported('busca UTM sem source/medium/campaign');
        }

        return $this->get('/api/utm', $query);
    }

    public function updateCampaignMetrics(int $campaignId, array $metrics): array
    {
        return $this->post("/api/campaigns/{$campaignId}/sync-metrics", $metrics);
    }

    public function attributeLeadToCampaign(int $leadId, int $campaignId): array
    {
        return $this->post('/api/conversions/lead', [
            'id' => $leadId,
            'utm_campaign' => (string) $campaignId,
        ]);
    }

    public function getLeadSource(int $leadId): array
    {
        return $this->unsupported("origem do lead {$leadId}");
    }

    public function addToList(int $listId, array $contact): array
    {
        return $this->unsupported("lista {$listId}");
    }

    public function removeFromList(int $listId, string $email): array
    {
        return $this->unsupported("remover {$email} da lista {$listId}");
    }

    public function dispatchEvent(string $eventType, array $payload): array
    {
        $type = match (true) {
            str_contains($eventType, 'contact') => 'contact',
            str_contains($eventType, 'registration') => 'registration',
            default => 'lead',
        };

        return $this->post('/api/webhooks/conversion', [
            'type' => $type,
            'lead' => $payload['lead'] ?? $payload,
            'contact' => $payload['contact'] ?? $payload,
            'source' => is_array($payload['source'] ?? null) ? $payload['source'] : [],
        ]);
    }
}
