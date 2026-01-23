<?php

namespace App\Services\Integrations\Site;

use App\Services\Integrations\BaseClient;

/**
 * Cliente para integracao com o Site (site.carinho.com.vc).
 *
 * Responsavel por:
 * - Recepcao de leads de formularios
 * - Tracking de UTM
 * - Eventos de conversao
 */
class SiteClient extends BaseClient
{
    protected string $configKey = 'site';

    /*
    |--------------------------------------------------------------------------
    | Leads
    |--------------------------------------------------------------------------
    */

    /**
     * Confirma recebimento de lead.
     */
    public function confirmLeadReceived(string $leadRef): array
    {
        return $this->post('/api/v1/leads/confirm', [
            'ref' => $leadRef,
            'received_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Atualiza status do lead no site.
     */
    public function updateLeadStatus(string $leadRef, string $status): array
    {
        return $this->put("/api/v1/leads/{$leadRef}/status", [
            'status' => $status,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Conversoes
    |--------------------------------------------------------------------------
    */

    /**
     * Registra conversao de lead.
     */
    public function trackConversion(string $leadRef, array $data): array
    {
        return $this->post('/api/v1/conversions', [
            'lead_ref' => $leadRef,
            'type' => $data['type'] ?? 'signup',
            'value' => $data['value'] ?? 0,
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UTM e Tracking
    |--------------------------------------------------------------------------
    */

    /**
     * Busca dados de UTM de um lead.
     */
    public function getLeadUtm(string $leadRef): array
    {
        return $this->get("/api/v1/leads/{$leadRef}/utm");
    }

    /*
    |--------------------------------------------------------------------------
    | Formularios
    |--------------------------------------------------------------------------
    */

    /**
     * Busca configuracao de formularios.
     */
    public function getFormConfig(string $formId): array
    {
        return $this->getCached("/api/v1/forms/{$formId}");
    }

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */

    /**
     * Dispara evento para Site.
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
