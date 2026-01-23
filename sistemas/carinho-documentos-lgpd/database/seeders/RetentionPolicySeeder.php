<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RetentionPolicySeeder extends Seeder
{
    /**
     * Seed the retention policies.
     */
    public function run(): void
    {
        DB::table('retention_policies')->insert([
            // Contratos: 10 anos (3650 dias)
            ['doc_type_id' => 1, 'retention_days' => 3650], // contrato_cliente
            ['doc_type_id' => 2, 'retention_days' => 3650], // contrato_cuidador

            // Termos e politicas: 5 anos (1825 dias)
            ['doc_type_id' => 3, 'retention_days' => 1825], // termos
            ['doc_type_id' => 4, 'retention_days' => 1825], // privacidade
        ]);
    }
}
