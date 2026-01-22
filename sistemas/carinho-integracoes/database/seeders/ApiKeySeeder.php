<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use Illuminate\Database\Seeder;

class ApiKeySeeder extends Seeder
{
    /**
     * Cria API Keys para os sistemas internos.
     */
    public function run(): void
    {
        $systems = [
            'site' => ['events:write', 'leads:write'],
            'crm' => ['*'],
            'atendimento' => ['events:write', 'events:read', 'whatsapp:*'],
            'operacao' => ['events:write', 'events:read', 'sync:*'],
            'financeiro' => ['events:write', 'events:read', 'sync:*'],
            'cuidadores' => ['events:write', 'events:read'],
            'marketing' => ['events:read', 'leads:read'],
            'documentos' => ['events:write'],
        ];

        foreach ($systems as $name => $permissions) {
            $result = ApiKey::generate("sistema-{$name}", $permissions);

            $this->command->info("API Key for {$name}: {$result['plain_key']}");
        }

        $this->command->warn('Save these API keys! They will not be shown again.');
    }
}
