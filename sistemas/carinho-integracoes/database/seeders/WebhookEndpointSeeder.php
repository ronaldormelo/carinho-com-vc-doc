<?php

namespace Database\Seeders;

use App\Models\WebhookEndpoint;
use Illuminate\Database\Seeder;

class WebhookEndpointSeeder extends Seeder
{
    /**
     * Cria endpoints de webhook para os sistemas.
     */
    public function run(): void
    {
        $endpoints = [
            [
                'system_name' => 'crm',
                'url' => env('CARINHO_CRM_URL', 'https://crm.carinho.com.vc') . '/webhooks/internal/site/lead',
            ],
            [
                'system_name' => 'operacao',
                'url' => env('CARINHO_OPERACAO_URL', 'https://operacao.carinho.com.vc') . '/api/webhooks/atendimento',
            ],
            [
                'system_name' => 'financeiro',
                'url' => env('CARINHO_FINANCEIRO_URL', 'https://financeiro.carinho.com.vc') . '/webhooks/internal',
            ],
            [
                'system_name' => 'cuidadores',
                'url' => env('CARINHO_CUIDADORES_URL', 'https://cuidadores.carinho.com.vc') . '/api/webhooks/operacao',
            ],
            [
                'system_name' => 'atendimento',
                'url' => env('CARINHO_ATENDIMENTO_URL', 'https://atendimento.carinho.com.vc') . '/api/inbox',
            ],
            [
                'system_name' => 'marketing',
                'url' => env('CARINHO_MARKETING_URL', 'https://marketing.carinho.com.vc') . '/api/webhooks/conversion',
            ],
        ];

        foreach ($endpoints as $endpoint) {
            $created = WebhookEndpoint::createWithSecret(
                $endpoint['system_name'],
                $endpoint['url']
            );

            $this->command->info("Endpoint for {$endpoint['system_name']}: Secret = {$created->secret}");
        }

        $this->command->warn('Save these secrets! They will not be shown again.');
    }
}
