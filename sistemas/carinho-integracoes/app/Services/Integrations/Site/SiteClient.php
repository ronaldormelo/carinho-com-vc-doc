<?php

namespace App\Services\Integrations\Site;

use App\Services\Integrations\BaseClient;

/**
 * Cliente do Site. Rotas reais: /api/leads, /api/webhooks/*, /api/content/*.
 */
class SiteClient extends BaseClient
{
    protected string $configKey = 'site';

    public function confirmLeadReceived(string $leadRef): array
    {
        return $this->post("/api/leads/{$leadRef}/mark-synced");
    }

    public function updateLeadStatus(string $leadRef, string $status): array
    {
        return $this->unsupported("status do lead {$leadRef} ({$status})");
    }

    public function trackConversion(string $leadRef, array $data): array
    {
        return $this->unsupported("conversão do lead {$leadRef}");
    }

    public function getLeadUtm(string $leadRef): array
    {
        return $this->get("/api/leads/{$leadRef}");
    }

    public function getFormConfig(string $formId): array
    {
        return $this->unsupported("configuração do formulário {$formId}");
    }

    public function dispatchEvent(string $eventType, array $payload): array
    {
        return $this->post('/api/webhooks/crm', [
            'event' => $eventType,
            'data' => $payload,
        ]);
    }
}
