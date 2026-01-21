<?php

namespace App\Services\Integrations;

/**
 * Integração com Carinho Site (site.carinho.com.vc)
 * Recebe leads de formulários e integra UTM tracking
 */
class CarinhoSiteService extends BaseInternalService
{
    protected string $serviceName = 'carinho-site';

    public function isEnabled(): bool
    {
        return config('integrations.internal.site.enabled', true) 
            && !empty($this->apiKey);
    }

    /**
     * Notifica o site sobre conversão de lead
     */
    public function notifyLeadConverted(int $leadId, array $leadData): ?array
    {
        return $this->post('webhooks/lead-converted', [
            'lead_id' => $leadId,
            'name' => $leadData['name'],
            'converted_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Obtém dados de UTM por ID
     */
    public function getUtmData(int $utmId): ?array
    {
        return $this->get("utm/{$utmId}");
    }

    /**
     * Registra conversão para tracking
     */
    public function trackConversion(int $leadId, string $conversionType, ?float $value = null): ?array
    {
        return $this->post('analytics/conversion', [
            'lead_id' => $leadId,
            'type' => $conversionType,
            'value' => $value,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
