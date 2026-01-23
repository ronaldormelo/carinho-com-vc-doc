<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration para adicionar melhorias baseadas em práticas tradicionais consolidadas:
 * 
 * 1. Classificação de clientes (A, B, C)
 * 2. Responsável financeiro separado
 * 3. Contato de emergência
 * 4. Probabilidade de fechamento em deals
 * 5. Controle de revisões periódicas
 * 6. Alertas de renovação configuráveis
 * 7. Tipos de evento padronizados para histórico
 * 8. Programa de indicação
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabela de domínio: Classificação de clientes (A, B, C)
        Schema::create('domain_client_classification', function (Blueprint $table) {
            $table->tinyInteger('id')->unsigned()->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
            $table->string('description', 255)->nullable();
            $table->tinyInteger('priority')->unsigned()->default(0)->comment('Ordem de prioridade para ordenação');
        });

        DB::table('domain_client_classification')->insert([
            ['id' => 1, 'code' => 'A', 'label' => 'Cliente A', 'description' => 'Alto valor/potencial - Prioridade máxima', 'priority' => 1],
            ['id' => 2, 'code' => 'B', 'label' => 'Cliente B', 'description' => 'Valor médio - Atenção regular', 'priority' => 2],
            ['id' => 3, 'code' => 'C', 'label' => 'Cliente C', 'description' => 'Valor baixo - Atendimento padrão', 'priority' => 3],
        ]);

        // 2. Tabela de domínio: Tipos de evento para histórico padronizado
        Schema::create('domain_event_type', function (Blueprint $table) {
            $table->tinyInteger('id')->unsigned()->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
            $table->string('category', 32)->comment('Categoria: commercial, operational, financial, communication');
        });

        DB::table('domain_event_type')->insert([
            // Comercial
            ['id' => 1, 'code' => 'lead_created', 'label' => 'Lead Criado', 'category' => 'commercial'],
            ['id' => 2, 'code' => 'lead_qualified', 'label' => 'Lead Qualificado', 'category' => 'commercial'],
            ['id' => 3, 'code' => 'proposal_sent', 'label' => 'Proposta Enviada', 'category' => 'commercial'],
            ['id' => 4, 'code' => 'proposal_accepted', 'label' => 'Proposta Aceita', 'category' => 'commercial'],
            ['id' => 5, 'code' => 'proposal_rejected', 'label' => 'Proposta Recusada', 'category' => 'commercial'],
            ['id' => 6, 'code' => 'deal_won', 'label' => 'Negócio Fechado', 'category' => 'commercial'],
            ['id' => 7, 'code' => 'deal_lost', 'label' => 'Negócio Perdido', 'category' => 'commercial'],
            // Operacional
            ['id' => 10, 'code' => 'client_created', 'label' => 'Cliente Cadastrado', 'category' => 'operational'],
            ['id' => 11, 'code' => 'contract_created', 'label' => 'Contrato Criado', 'category' => 'operational'],
            ['id' => 12, 'code' => 'contract_signed', 'label' => 'Contrato Assinado', 'category' => 'operational'],
            ['id' => 13, 'code' => 'contract_activated', 'label' => 'Contrato Ativado', 'category' => 'operational'],
            ['id' => 14, 'code' => 'contract_renewed', 'label' => 'Contrato Renovado', 'category' => 'operational'],
            ['id' => 15, 'code' => 'contract_closed', 'label' => 'Contrato Encerrado', 'category' => 'operational'],
            ['id' => 16, 'code' => 'review_scheduled', 'label' => 'Revisão Agendada', 'category' => 'operational'],
            ['id' => 17, 'code' => 'review_completed', 'label' => 'Revisão Realizada', 'category' => 'operational'],
            // Financeiro
            ['id' => 20, 'code' => 'payment_received', 'label' => 'Pagamento Recebido', 'category' => 'financial'],
            ['id' => 21, 'code' => 'payment_overdue', 'label' => 'Pagamento em Atraso', 'category' => 'financial'],
            ['id' => 22, 'code' => 'invoice_sent', 'label' => 'Fatura Enviada', 'category' => 'financial'],
            // Comunicação
            ['id' => 30, 'code' => 'contact_whatsapp', 'label' => 'Contato WhatsApp', 'category' => 'communication'],
            ['id' => 31, 'code' => 'contact_phone', 'label' => 'Contato Telefone', 'category' => 'communication'],
            ['id' => 32, 'code' => 'contact_email', 'label' => 'Contato E-mail', 'category' => 'communication'],
            ['id' => 33, 'code' => 'complaint', 'label' => 'Reclamação Registrada', 'category' => 'communication'],
            ['id' => 34, 'code' => 'feedback_positive', 'label' => 'Feedback Positivo', 'category' => 'communication'],
            ['id' => 35, 'code' => 'feedback_negative', 'label' => 'Feedback Negativo', 'category' => 'communication'],
            ['id' => 36, 'code' => 'referral_made', 'label' => 'Indicação Realizada', 'category' => 'communication'],
        ]);

        // 3. Tabela de domínio: Frequência de revisão
        Schema::create('domain_review_frequency', function (Blueprint $table) {
            $table->tinyInteger('id')->unsigned()->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
            $table->smallInteger('days')->unsigned()->comment('Intervalo em dias');
        });

        DB::table('domain_review_frequency')->insert([
            ['id' => 1, 'code' => 'monthly', 'label' => 'Mensal', 'days' => 30],
            ['id' => 2, 'code' => 'bimonthly', 'label' => 'Bimestral', 'days' => 60],
            ['id' => 3, 'code' => 'quarterly', 'label' => 'Trimestral', 'days' => 90],
            ['id' => 4, 'code' => 'semiannual', 'label' => 'Semestral', 'days' => 180],
            ['id' => 5, 'code' => 'annual', 'label' => 'Anual', 'days' => 365],
        ]);

        // 4. Adicionar campos na tabela clients
        Schema::table('clients', function (Blueprint $table) {
            // Classificação ABC
            $table->tinyInteger('classification_id')->unsigned()->nullable()->after('preferences_json');
            $table->foreign('classification_id')
                ->references('id')
                ->on('domain_client_classification')
                ->nullOnDelete();

            // Responsável financeiro (separado do contato principal)
            $table->string('financial_contact_name', 255)->nullable()->after('classification_id');
            $table->text('financial_contact_phone')->nullable()->after('financial_contact_name'); // Criptografado
            $table->text('financial_contact_email')->nullable()->after('financial_contact_phone'); // Criptografado
            $table->string('financial_contact_cpf_cnpj', 20)->nullable()->after('financial_contact_email');

            // Contato de emergência (crítico para HomeCare)
            $table->string('emergency_contact_name', 255)->nullable()->after('financial_contact_cpf_cnpj');
            $table->text('emergency_contact_phone')->nullable()->after('emergency_contact_name'); // Criptografado
            $table->string('emergency_contact_relationship', 64)->nullable()->after('emergency_contact_phone');

            // Controle de revisões periódicas
            $table->tinyInteger('review_frequency_id')->unsigned()->nullable()->after('emergency_contact_relationship');
            $table->foreign('review_frequency_id')
                ->references('id')
                ->on('domain_review_frequency')
                ->nullOnDelete();
            $table->date('next_review_date')->nullable()->after('review_frequency_id');
            $table->date('last_review_date')->nullable()->after('next_review_date');

            // Programa de indicação
            $table->unsignedBigInteger('referred_by_client_id')->nullable()->after('last_review_date');
            $table->foreign('referred_by_client_id')
                ->references('id')
                ->on('clients')
                ->nullOnDelete();
            $table->string('referral_source', 128)->nullable()->after('referred_by_client_id');

            // Observações gerais (campo tradicional importante)
            $table->text('internal_notes')->nullable()->after('referral_source');

            // Índices
            $table->index('classification_id', 'idx_clients_classification');
            $table->index('next_review_date', 'idx_clients_next_review');
        });

        // 5. Adicionar campos na tabela deals (probabilidade de fechamento)
        Schema::table('deals', function (Blueprint $table) {
            // Probabilidade de fechamento (prática tradicional de vendas)
            $table->tinyInteger('probability')->unsigned()->default(50)->after('value_estimated')
                ->comment('Probabilidade de fechamento em %: 10, 25, 50, 75, 90');
            
            // Valor ponderado (valor * probabilidade para forecast)
            $table->decimal('weighted_value', 12, 2)->storedAs('value_estimated * probability / 100')->after('probability');

            // Data prevista de fechamento
            $table->date('expected_close_date')->nullable()->after('weighted_value');
            
            // Próximo passo (prática tradicional de vendas)
            $table->string('next_action', 255)->nullable()->after('expected_close_date');
            $table->date('next_action_date')->nullable()->after('next_action');

            // Índices
            $table->index('expected_close_date', 'idx_deals_expected_close');
            $table->index('probability', 'idx_deals_probability');
        });

        // 6. Adicionar campos na tabela contracts (alertas configuráveis)
        Schema::table('contracts', function (Blueprint $table) {
            // Dias de antecedência para alerta de renovação (configurável por contrato)
            $table->smallInteger('renewal_alert_days')->unsigned()->default(30)->after('end_date')
                ->comment('Dias antes do vencimento para alertar');
            
            // Data do último alerta enviado (evita spam)
            $table->date('last_renewal_alert_at')->nullable()->after('renewal_alert_days');
            
            // Renovação automática (para contratos recorrentes)
            $table->boolean('auto_renewal')->default(false)->after('last_renewal_alert_at');
            
            // Número de renovações realizadas
            $table->smallInteger('renewal_count')->unsigned()->default(0)->after('auto_renewal');
            
            // Contrato original (para rastrear renovações)
            $table->unsignedBigInteger('original_contract_id')->nullable()->after('renewal_count');
            $table->foreign('original_contract_id')
                ->references('id')
                ->on('contracts')
                ->nullOnDelete();
        });

        // 7. Tabela de histórico de eventos padronizado (timeline estruturada)
        Schema::create('client_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->tinyInteger('event_type_id')->unsigned();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable()->comment('Dados adicionais do evento em JSON');
            $table->unsignedBigInteger('related_id')->nullable()->comment('ID da entidade relacionada (deal, contract, etc)');
            $table->string('related_type', 64)->nullable()->comment('Tipo da entidade relacionada');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Usuário que criou o evento');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->cascadeOnDelete();

            $table->foreign('event_type_id')
                ->references('id')
                ->on('domain_event_type');

            // Índices para consultas frequentes
            $table->index(['client_id', 'occurred_at'], 'idx_client_events_timeline');
            $table->index(['event_type_id'], 'idx_client_events_type');
            $table->index(['related_id', 'related_type'], 'idx_client_events_related');
        });

        // 8. Tabela de revisões de clientes
        Schema::create('client_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->date('review_date');
            $table->tinyInteger('satisfaction_score')->unsigned()->nullable()
                ->comment('Nota de satisfação 1-5');
            $table->tinyInteger('service_quality_score')->unsigned()->nullable()
                ->comment('Nota de qualidade do serviço 1-5');
            $table->boolean('contract_renewal_intent')->nullable()
                ->comment('Cliente pretende renovar?');
            $table->text('observations')->nullable();
            $table->text('action_items')->nullable()
                ->comment('Ações identificadas na revisão');
            $table->date('next_review_date')->nullable();
            $table->timestamps();

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->cascadeOnDelete();

            $table->index(['client_id', 'review_date'], 'idx_client_reviews_date');
        });

        // 9. Tabela de indicações (programa de referral)
        Schema::create('client_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_client_id')->comment('Cliente que indicou');
            $table->unsignedBigInteger('referred_lead_id')->nullable()->comment('Lead indicado');
            $table->unsignedBigInteger('referred_client_id')->nullable()->comment('Cliente convertido da indicação');
            $table->string('referred_name', 255);
            $table->string('referred_phone', 32)->nullable();
            $table->string('status', 32)->default('pending')
                ->comment('pending, contacted, converted, lost');
            $table->text('notes')->nullable();
            $table->date('converted_at')->nullable();
            $table->timestamps();

            $table->foreign('referrer_client_id')
                ->references('id')
                ->on('clients')
                ->cascadeOnDelete();

            $table->foreign('referred_lead_id')
                ->references('id')
                ->on('leads')
                ->nullOnDelete();

            $table->foreign('referred_client_id')
                ->references('id')
                ->on('clients')
                ->nullOnDelete();

            $table->index(['referrer_client_id', 'status'], 'idx_referrals_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover tabelas criadas
        Schema::dropIfExists('client_referrals');
        Schema::dropIfExists('client_reviews');
        Schema::dropIfExists('client_events');

        // Remover campos de contracts
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['original_contract_id']);
            $table->dropColumn([
                'renewal_alert_days',
                'last_renewal_alert_at',
                'auto_renewal',
                'renewal_count',
                'original_contract_id',
            ]);
        });

        // Remover campos de deals
        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex('idx_deals_expected_close');
            $table->dropIndex('idx_deals_probability');
            $table->dropColumn([
                'probability',
                'weighted_value',
                'expected_close_date',
                'next_action',
                'next_action_date',
            ]);
        });

        // Remover campos de clients
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['classification_id']);
            $table->dropForeign(['review_frequency_id']);
            $table->dropForeign(['referred_by_client_id']);
            $table->dropIndex('idx_clients_classification');
            $table->dropIndex('idx_clients_next_review');
            $table->dropColumn([
                'classification_id',
                'financial_contact_name',
                'financial_contact_phone',
                'financial_contact_email',
                'financial_contact_cpf_cnpj',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relationship',
                'review_frequency_id',
                'next_review_date',
                'last_review_date',
                'referred_by_client_id',
                'referral_source',
                'internal_notes',
            ]);
        });

        // Remover tabelas de domínio
        Schema::dropIfExists('domain_review_frequency');
        Schema::dropIfExists('domain_event_type');
        Schema::dropIfExists('domain_client_classification');
    }
};
