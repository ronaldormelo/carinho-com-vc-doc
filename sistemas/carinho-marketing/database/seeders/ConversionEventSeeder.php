<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConversionEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            [
                'name' => 'Novo Lead',
                'event_key' => 'Lead',
                'target_url' => 'https://carinho.com.vc/obrigado',
                'created_at' => now(),
            ],
            [
                'name' => 'Contato WhatsApp',
                'event_key' => 'Contact',
                'target_url' => 'https://wa.me/5511999999999',
                'created_at' => now(),
            ],
            [
                'name' => 'Cadastro Completo',
                'event_key' => 'CompleteRegistration',
                'target_url' => 'https://carinho.com.vc/cadastro-completo',
                'created_at' => now(),
            ],
            [
                'name' => 'Inicio de Contratacao',
                'event_key' => 'InitiateCheckout',
                'target_url' => 'https://carinho.com.vc/contratar',
                'created_at' => now(),
            ],
            [
                'name' => 'Contratacao Finalizada',
                'event_key' => 'Purchase',
                'target_url' => 'https://carinho.com.vc/contratacao-confirmada',
                'created_at' => now(),
            ],
            [
                'name' => 'Agendamento',
                'event_key' => 'Schedule',
                'target_url' => 'https://carinho.com.vc/agendado',
                'created_at' => now(),
            ],
        ];

        foreach ($events as $event) {
            DB::table('conversion_events')->updateOrInsert(
                ['event_key' => $event['event_key']],
                $event
            );
        }
    }
}
