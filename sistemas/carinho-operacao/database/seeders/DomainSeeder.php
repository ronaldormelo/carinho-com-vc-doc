<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tipos de servico
        DB::table('domain_service_type')->insert([
            ['id' => 1, 'code' => 'horista', 'label' => 'Horista'],
            ['id' => 2, 'code' => 'diario', 'label' => 'Diario'],
            ['id' => 3, 'code' => 'mensal', 'label' => 'Mensal'],
        ]);

        // Niveis de urgencia
        DB::table('domain_urgency_level')->insert([
            ['id' => 1, 'code' => 'hoje', 'label' => 'Hoje'],
            ['id' => 2, 'code' => 'semana', 'label' => 'Semana'],
            ['id' => 3, 'code' => 'sem_data', 'label' => 'Sem data'],
        ]);

        // Status de servico
        DB::table('domain_service_status')->insert([
            ['id' => 1, 'code' => 'open', 'label' => 'Aberto'],
            ['id' => 2, 'code' => 'scheduled', 'label' => 'Agendado'],
            ['id' => 3, 'code' => 'active', 'label' => 'Ativo'],
            ['id' => 4, 'code' => 'completed', 'label' => 'Concluido'],
            ['id' => 5, 'code' => 'canceled', 'label' => 'Cancelado'],
        ]);

        // Status de alocacao
        DB::table('domain_assignment_status')->insert([
            ['id' => 1, 'code' => 'assigned', 'label' => 'Alocado'],
            ['id' => 2, 'code' => 'confirmed', 'label' => 'Confirmado'],
            ['id' => 3, 'code' => 'replaced', 'label' => 'Substituido'],
            ['id' => 4, 'code' => 'canceled', 'label' => 'Cancelado'],
        ]);

        // Status de agendamento
        DB::table('domain_schedule_status')->insert([
            ['id' => 1, 'code' => 'planned', 'label' => 'Planejado'],
            ['id' => 2, 'code' => 'in_progress', 'label' => 'Em andamento'],
            ['id' => 3, 'code' => 'done', 'label' => 'Concluido'],
            ['id' => 4, 'code' => 'missed', 'label' => 'Perdido'],
        ]);

        // Tipos de checklist
        DB::table('domain_checklist_type')->insert([
            ['id' => 1, 'code' => 'start', 'label' => 'Inicio'],
            ['id' => 2, 'code' => 'end', 'label' => 'Fim'],
        ]);

        // Tipos de check
        DB::table('domain_check_type')->insert([
            ['id' => 1, 'code' => 'in', 'label' => 'Entrada'],
            ['id' => 2, 'code' => 'out', 'label' => 'Saida'],
        ]);

        // Status de notificacao
        DB::table('domain_notification_status')->insert([
            ['id' => 1, 'code' => 'queued', 'label' => 'Na fila'],
            ['id' => 2, 'code' => 'sent', 'label' => 'Enviado'],
            ['id' => 3, 'code' => 'failed', 'label' => 'Falhou'],
        ]);

        // Severidade de emergencia
        DB::table('domain_emergency_severity')->insert([
            ['id' => 1, 'code' => 'low', 'label' => 'Baixa'],
            ['id' => 2, 'code' => 'medium', 'label' => 'Media'],
            ['id' => 3, 'code' => 'high', 'label' => 'Alta'],
            ['id' => 4, 'code' => 'critical', 'label' => 'Critica'],
        ]);
    }
}
