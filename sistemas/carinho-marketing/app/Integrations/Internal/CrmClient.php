<?php

namespace App\Integrations\Internal;

use App\Integrations\BaseClient;

/**
 * Cliente para integracao com sistema CRM interno.
 *
 * Responsavel por enviar leads capturados e suas origens para o CRM.
 */
class CrmClient extends BaseClient
{
    public function __construct()
    {
        $this->baseUrl = config('integrations.crm.base_url', 'https://crm.carinho.com.vc/api');
        $this->timeout = (int) config('integrations.crm.timeout', 8);
        $this->connectTimeout = 3;
        $this->cachePrefix = 'crm';
    }

    /**
     * Envia lead para o CRM.
     */
    public function sendLead(array $leadData): array
    {
        return $this->post('/leads', $leadData);
    }

    /**
     * Atualiza origem do lead.
     */
    public function updateLeadSource(string $leadId, array $sourceData): array
    {
        return $this->put("/leads/{$leadId}/source", $sourceData);
    }

    /**
     * Busca lead por telefone.
     */
    public function findLeadByPhone(string $phone): array
    {
        return $this->get('/leads/search', ['phone' => $phone]);
    }

    /**
     * Busca lead por email.
     */
    public function findLeadByEmail(string $email): array
    {
        return $this->get('/leads/search', ['email' => $email]);
    }

    /**
     * Obtem estatisticas de leads por periodo.
     */
    public function getLeadStats(string $startDate, string $endDate): array
    {
        return $this->get('/leads/stats', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Obtem leads por origem.
     */
    public function getLeadsBySource(string $source, ?string $startDate = null, ?string $endDate = null): array
    {
        $params = ['source' => $source];

        if ($startDate) {
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $params['end_date'] = $endDate;
        }

        return $this->get('/leads/by-source', $params);
    }

    /**
     * Registra conversao de lead.
     */
    public function registerConversion(string $leadId, string $conversionType, array $data = []): array
    {
        return $this->post("/leads/{$leadId}/conversions", [
            'type' => $conversionType,
            'data' => $data,
            'converted_at' => now()->toIso8601String(),
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
            'Authorization' => 'Bearer ' . config('integrations.crm.token'),
            'X-Internal-Token' => config('integrations.internal.token'),
        ];
    }
}
