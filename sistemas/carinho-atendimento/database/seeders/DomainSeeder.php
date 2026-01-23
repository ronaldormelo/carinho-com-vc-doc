<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DomainSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('domain_channel')->insert([
            ['id' => 1, 'code' => 'whatsapp', 'label' => 'WhatsApp'],
            ['id' => 2, 'code' => 'email', 'label' => 'Email'],
            ['id' => 3, 'code' => 'phone', 'label' => 'Telefone'],
        ]);

        DB::table('domain_conversation_status')->insert([
            ['id' => 1, 'code' => 'new', 'label' => 'Novo'],
            ['id' => 2, 'code' => 'triage', 'label' => 'Triagem'],
            ['id' => 3, 'code' => 'proposal', 'label' => 'Proposta'],
            ['id' => 4, 'code' => 'waiting', 'label' => 'Aguardando'],
            ['id' => 5, 'code' => 'active', 'label' => 'Ativo'],
            ['id' => 6, 'code' => 'lost', 'label' => 'Perdido'],
            ['id' => 7, 'code' => 'closed', 'label' => 'Encerrado'],
        ]);

        DB::table('domain_priority')->insert([
            ['id' => 1, 'code' => 'low', 'label' => 'Baixa'],
            ['id' => 2, 'code' => 'normal', 'label' => 'Normal'],
            ['id' => 3, 'code' => 'high', 'label' => 'Alta'],
            ['id' => 4, 'code' => 'urgent', 'label' => 'Urgente'],
        ]);

        DB::table('domain_message_direction')->insert([
            ['id' => 1, 'code' => 'inbound', 'label' => 'Entrada'],
            ['id' => 2, 'code' => 'outbound', 'label' => 'Saida'],
        ]);

        DB::table('domain_message_status')->insert([
            ['id' => 1, 'code' => 'queued', 'label' => 'Na fila'],
            ['id' => 2, 'code' => 'sent', 'label' => 'Enviada'],
            ['id' => 3, 'code' => 'delivered', 'label' => 'Entregue'],
            ['id' => 4, 'code' => 'failed', 'label' => 'Falhou'],
        ]);

        DB::table('domain_agent_role')->insert([
            ['id' => 1, 'code' => 'agent', 'label' => 'Atendente'],
            ['id' => 2, 'code' => 'supervisor', 'label' => 'Supervisor'],
            ['id' => 3, 'code' => 'admin', 'label' => 'Administrador'],
        ]);

        DB::table('domain_incident_severity')->insert([
            ['id' => 1, 'code' => 'low', 'label' => 'Baixa'],
            ['id' => 2, 'code' => 'medium', 'label' => 'Media'],
            ['id' => 3, 'code' => 'high', 'label' => 'Alta'],
            ['id' => 4, 'code' => 'critical', 'label' => 'Critica'],
        ]);

        DB::table('domain_webhook_status')->insert([
            ['id' => 1, 'code' => 'pending', 'label' => 'Pendente'],
            ['id' => 2, 'code' => 'processed', 'label' => 'Processado'],
            ['id' => 3, 'code' => 'failed', 'label' => 'Falhou'],
        ]);

        // Novos domínios para melhorias

        DB::table('domain_support_level')->insert([
            ['id' => 1, 'code' => 'n1', 'label' => 'Nivel 1 - Atendimento', 'escalation_minutes' => 15],
            ['id' => 2, 'code' => 'n2', 'label' => 'Nivel 2 - Supervisao', 'escalation_minutes' => 30],
            ['id' => 3, 'code' => 'n3', 'label' => 'Nivel 3 - Gestao', 'escalation_minutes' => 60],
        ]);

        DB::table('domain_loss_reason')->insert([
            ['id' => 1, 'code' => 'price', 'label' => 'Preco acima do orcamento'],
            ['id' => 2, 'code' => 'competitor', 'label' => 'Escolheu concorrente'],
            ['id' => 3, 'code' => 'no_response', 'label' => 'Sem retorno do cliente'],
            ['id' => 4, 'code' => 'no_availability', 'label' => 'Sem disponibilidade de cuidador'],
            ['id' => 5, 'code' => 'region', 'label' => 'Regiao nao atendida'],
            ['id' => 6, 'code' => 'requirements', 'label' => 'Requisitos nao atendidos'],
            ['id' => 7, 'code' => 'postponed', 'label' => 'Cliente adiou a decisao'],
            ['id' => 8, 'code' => 'other', 'label' => 'Outro motivo'],
        ]);

        DB::table('domain_incident_category')->insert([
            ['id' => 1, 'code' => 'complaint', 'label' => 'Reclamacao'],
            ['id' => 2, 'code' => 'delay', 'label' => 'Atraso no atendimento'],
            ['id' => 3, 'code' => 'quality', 'label' => 'Qualidade do servico'],
            ['id' => 4, 'code' => 'communication', 'label' => 'Falha de comunicacao'],
            ['id' => 5, 'code' => 'billing', 'label' => 'Problema de cobranca'],
            ['id' => 6, 'code' => 'caregiver', 'label' => 'Problema com cuidador'],
            ['id' => 7, 'code' => 'emergency', 'label' => 'Emergencia'],
            ['id' => 8, 'code' => 'suggestion', 'label' => 'Sugestao'],
            ['id' => 9, 'code' => 'other', 'label' => 'Outros'],
        ]);

        DB::table('domain_action_type')->insert([
            ['id' => 1, 'code' => 'status_change', 'label' => 'Mudanca de status'],
            ['id' => 2, 'code' => 'priority_change', 'label' => 'Mudanca de prioridade'],
            ['id' => 3, 'code' => 'assignment', 'label' => 'Atribuicao de atendente'],
            ['id' => 4, 'code' => 'escalation', 'label' => 'Escalonamento'],
            ['id' => 5, 'code' => 'note', 'label' => 'Anotacao interna'],
            ['id' => 6, 'code' => 'tag', 'label' => 'Adicao de etiqueta'],
            ['id' => 7, 'code' => 'incident', 'label' => 'Registro de incidente'],
            ['id' => 8, 'code' => 'closure', 'label' => 'Encerramento'],
        ]);

        // Metas de SLA por prioridade
        DB::table('sla_targets')->insert([
            ['priority_id' => 1, 'first_response_minutes' => 60, 'resolution_minutes' => 480],
            ['priority_id' => 2, 'first_response_minutes' => 30, 'resolution_minutes' => 240],
            ['priority_id' => 3, 'first_response_minutes' => 15, 'resolution_minutes' => 120],
            ['priority_id' => 4, 'first_response_minutes' => 5, 'resolution_minutes' => 60],
        ]);

        // Checklist de triagem padrão
        DB::table('triage_checklist')->insert([
            ['item_key' => 'patient_name', 'item_label' => 'Nome do paciente', 'item_order' => 1, 'required' => 1, 'active' => 1],
            ['item_key' => 'patient_age', 'item_label' => 'Idade do paciente', 'item_order' => 2, 'required' => 1, 'active' => 1],
            ['item_key' => 'care_type', 'item_label' => 'Tipo de cuidado necessario', 'item_order' => 3, 'required' => 1, 'active' => 1],
            ['item_key' => 'location', 'item_label' => 'Cidade/bairro do atendimento', 'item_order' => 4, 'required' => 1, 'active' => 1],
            ['item_key' => 'schedule', 'item_label' => 'Horario/turno desejado', 'item_order' => 5, 'required' => 1, 'active' => 1],
            ['item_key' => 'start_date', 'item_label' => 'Data de inicio pretendida', 'item_order' => 6, 'required' => 1, 'active' => 1],
            ['item_key' => 'special_needs', 'item_label' => 'Necessidades especiais', 'item_order' => 7, 'required' => 0, 'active' => 1],
            ['item_key' => 'budget', 'item_label' => 'Expectativa de valor', 'item_order' => 8, 'required' => 0, 'active' => 1],
            ['item_key' => 'decision_maker', 'item_label' => 'Quem decide a contratacao', 'item_order' => 9, 'required' => 0, 'active' => 1],
            ['item_key' => 'how_found_us', 'item_label' => 'Como conheceu a Carinho', 'item_order' => 10, 'required' => 0, 'active' => 1],
        ]);

        // Feriados nacionais
        DB::table('holidays')->insert([
            ['date' => '2026-01-01', 'description' => 'Confraternizacao Universal', 'year_recurring' => 1],
            ['date' => '2026-04-21', 'description' => 'Tiradentes', 'year_recurring' => 1],
            ['date' => '2026-05-01', 'description' => 'Dia do Trabalho', 'year_recurring' => 1],
            ['date' => '2026-09-07', 'description' => 'Independencia do Brasil', 'year_recurring' => 1],
            ['date' => '2026-10-12', 'description' => 'Nossa Senhora Aparecida', 'year_recurring' => 1],
            ['date' => '2026-11-02', 'description' => 'Finados', 'year_recurring' => 1],
            ['date' => '2026-11-15', 'description' => 'Proclamacao da Republica', 'year_recurring' => 1],
            ['date' => '2026-12-25', 'description' => 'Natal', 'year_recurring' => 1],
        ]);
    }
}
