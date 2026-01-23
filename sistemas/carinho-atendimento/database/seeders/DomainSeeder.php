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
            ['id' => 2, 'code' => 'triage', 'label' => 'Em Triagem'],
            ['id' => 3, 'code' => 'proposal', 'label' => 'Proposta Enviada'],
            ['id' => 4, 'code' => 'waiting', 'label' => 'Aguardando Resposta'],
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
            ['id' => 1, 'code' => 'inbound', 'label' => 'Recebida'],
            ['id' => 2, 'code' => 'outbound', 'label' => 'Enviada'],
        ]);

        DB::table('domain_message_status')->insert([
            ['id' => 1, 'code' => 'queued', 'label' => 'Na Fila'],
            ['id' => 2, 'code' => 'sent', 'label' => 'Enviada'],
            ['id' => 3, 'code' => 'delivered', 'label' => 'Entregue'],
            ['id' => 4, 'code' => 'failed', 'label' => 'Falha'],
            ['id' => 5, 'code' => 'read', 'label' => 'Lida'],
        ]);

        DB::table('domain_agent_role')->insert([
            ['id' => 1, 'code' => 'agent', 'label' => 'Atendente'],
            ['id' => 2, 'code' => 'supervisor', 'label' => 'Supervisor'],
            ['id' => 3, 'code' => 'admin', 'label' => 'Administrador'],
        ]);

        DB::table('domain_incident_severity')->insert([
            ['id' => 1, 'code' => 'low', 'label' => 'Baixa'],
            ['id' => 2, 'code' => 'medium', 'label' => 'Média'],
            ['id' => 3, 'code' => 'high', 'label' => 'Alta'],
            ['id' => 4, 'code' => 'critical', 'label' => 'Crítica'],
        ]);

        DB::table('domain_webhook_status')->insert([
            ['id' => 1, 'code' => 'pending', 'label' => 'Pendente'],
            ['id' => 2, 'code' => 'processed', 'label' => 'Processado'],
            ['id' => 3, 'code' => 'failed', 'label' => 'Falha'],
        ]);

        // Níveis de suporte
        DB::table('domain_support_level')->insert([
            ['id' => 1, 'code' => 'n1', 'label' => 'Nível 1 - Atendimento', 'description' => 'Primeiro contato, triagem e informações básicas', 'max_response_minutes' => 5, 'max_resolution_minutes' => 30],
            ['id' => 2, 'code' => 'n2', 'label' => 'Nível 2 - Suporte', 'description' => 'Questões técnicas, propostas e negociação', 'max_response_minutes' => 15, 'max_resolution_minutes' => 120],
            ['id' => 3, 'code' => 'n3', 'label' => 'Nível 3 - Especialista', 'description' => 'Casos complexos, reclamações críticas e emergências', 'max_response_minutes' => 30, 'max_resolution_minutes' => 240],
        ]);

        // Motivos de perda
        DB::table('domain_loss_reason')->insert([
            ['id' => 1, 'code' => 'price', 'label' => 'Preço acima do orçamento', 'requires_notes' => 0],
            ['id' => 2, 'code' => 'timing', 'label' => 'Prazo não atendeu', 'requires_notes' => 0],
            ['id' => 3, 'code' => 'competitor', 'label' => 'Optou pela concorrência', 'requires_notes' => 1],
            ['id' => 4, 'code' => 'no_caregiver', 'label' => 'Não encontramos cuidador adequado', 'requires_notes' => 0],
            ['id' => 5, 'code' => 'no_response', 'label' => 'Cliente não respondeu', 'requires_notes' => 0],
            ['id' => 6, 'code' => 'changed_mind', 'label' => 'Cliente desistiu do serviço', 'requires_notes' => 1],
            ['id' => 7, 'code' => 'location', 'label' => 'Região não atendida', 'requires_notes' => 0],
            ['id' => 8, 'code' => 'schedule', 'label' => 'Horário incompatível', 'requires_notes' => 0],
            ['id' => 9, 'code' => 'other', 'label' => 'Outro motivo', 'requires_notes' => 1],
        ]);

        // Categorias de scripts
        DB::table('domain_script_category')->insert([
            ['id' => 1, 'code' => 'greeting', 'label' => 'Saudação'],
            ['id' => 2, 'code' => 'qualification', 'label' => 'Qualificação'],
            ['id' => 3, 'code' => 'proposal', 'label' => 'Proposta'],
            ['id' => 4, 'code' => 'objection', 'label' => 'Objeção'],
            ['id' => 5, 'code' => 'closing', 'label' => 'Fechamento'],
            ['id' => 6, 'code' => 'support', 'label' => 'Suporte'],
            ['id' => 7, 'code' => 'emergency', 'label' => 'Emergência'],
            ['id' => 8, 'code' => 'feedback', 'label' => 'Feedback'],
        ]);

        // Tipos de ação (auditoria)
        DB::table('domain_action_type')->insert([
            ['id' => 1, 'code' => 'status_change', 'label' => 'Mudança de Status'],
            ['id' => 2, 'code' => 'priority_change', 'label' => 'Mudança de Prioridade'],
            ['id' => 3, 'code' => 'assignment', 'label' => 'Atribuição de Agente'],
            ['id' => 4, 'code' => 'escalation', 'label' => 'Escalonamento'],
            ['id' => 5, 'code' => 'note_added', 'label' => 'Nota Adicionada'],
            ['id' => 6, 'code' => 'tag_added', 'label' => 'Tag Adicionada'],
            ['id' => 7, 'code' => 'incident_created', 'label' => 'Incidente Criado'],
            ['id' => 8, 'code' => 'sla_breach', 'label' => 'Violação de SLA'],
        ]);
    }
}
