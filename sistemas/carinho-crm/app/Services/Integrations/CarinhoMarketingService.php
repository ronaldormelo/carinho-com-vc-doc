<?php

namespace App\Services\Integrations;

/**
 * Integração com Carinho Marketing (marketing.carinho.com.vc)
 * Gestão de campanhas e tracking de leads
 */
class CarinhoMarketingService extends BaseInternalService
{
    protected string $serviceName = 'carinho-marketing';

    public function isEnabled(): bool
    {
        return config('integrations.internal.marketing.enabled', true) 
            && !empty($this->apiKey);
    }

    /**
     * Registra lead com informações de campanha
     */
    public function registerLeadSource(int $leadId, array $sourceData): ?array
    {
        return $this->post('leads/source', [
            'lead_id' => $leadId,
            'source' => $sourceData['source'] ?? 'unknown',
            'medium' => $sourceData['medium'] ?? null,
            'campaign' => $sourceData['campaign'] ?? null,
            'utm_source' => $sourceData['utm_source'] ?? null,
            'utm_medium' => $sourceData['utm_medium'] ?? null,
            'utm_campaign' => $sourceData['utm_campaign'] ?? null,
            'utm_content' => $sourceData['utm_content'] ?? null,
            'utm_term' => $sourceData['utm_term'] ?? null,
            'created_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Notifica conversão para atribuição de campanha
     */
    public function notifyConversion(int $leadId, float $value): ?array
    {
        return $this->post('conversions', [
            'lead_id' => $leadId,
            'value' => $value,
            'converted_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Obtém performance de campanha
     */
    public function getCampaignPerformance(string $campaignId): ?array
    {
        return $this->get("campaigns/{$campaignId}/performance");
    }

    /**
     * Lista fontes de lead ativas
     */
    public function getActiveSources(): ?array
    {
        return $this->get('sources/active');
    }
}
