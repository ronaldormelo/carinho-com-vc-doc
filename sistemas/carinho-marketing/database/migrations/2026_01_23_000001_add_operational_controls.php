<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adiciona controles operacionais para:
     * - Aprovação de orçamento de campanhas
     * - Limites de gastos
     * - Histórico de alterações (auditoria)
     * - Parcerias locais
     * - Indicações de clientes
     */
    public function up(): void
    {
        // Tabela de domínio para status de aprovação
        Schema::create('domain_approval_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_approval_status')->insert([
            ['id' => 1, 'code' => 'pending', 'label' => 'Pendente'],
            ['id' => 2, 'code' => 'approved', 'label' => 'Aprovado'],
            ['id' => 3, 'code' => 'rejected', 'label' => 'Rejeitado'],
        ]);

        // Tabela de domínio para tipo de parceria
        Schema::create('domain_partnership_type', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_partnership_type')->insert([
            ['id' => 1, 'code' => 'clinic', 'label' => 'Clínica'],
            ['id' => 2, 'code' => 'hospital', 'label' => 'Hospital'],
            ['id' => 3, 'code' => 'caregiver', 'label' => 'Cuidador'],
            ['id' => 4, 'code' => 'condominium', 'label' => 'Condomínio'],
            ['id' => 5, 'code' => 'pharmacy', 'label' => 'Farmácia'],
            ['id' => 6, 'code' => 'other', 'label' => 'Outro'],
        ]);

        // Tabela de domínio para status de parceria
        Schema::create('domain_partnership_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_partnership_status')->insert([
            ['id' => 1, 'code' => 'active', 'label' => 'Ativo'],
            ['id' => 2, 'code' => 'inactive', 'label' => 'Inativo'],
            ['id' => 3, 'code' => 'pending', 'label' => 'Pendente'],
        ]);

        // Aprovações de orçamento de campanhas
        Schema::create('campaign_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->decimal('requested_budget', 12, 2);
            $table->unsignedTinyInteger('status_id')->default(1);
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('justification')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->foreign('status_id')->references('id')->on('domain_approval_status');
            $table->index(['campaign_id', 'status_id']);
        });

        // Configuração de limites de gastos
        Schema::create('budget_limits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->nullable(); // null = limite global
            $table->decimal('daily_limit', 12, 2)->nullable();
            $table->decimal('monthly_limit', 12, 2)->nullable();
            $table->decimal('total_limit', 12, 2)->nullable();
            $table->boolean('auto_pause_enabled')->default(false);
            $table->tinyInteger('alert_threshold_70')->default(1); // 1 = ativo
            $table->tinyInteger('alert_threshold_90')->default(1);
            $table->tinyInteger('alert_threshold_100')->default(1);
            $table->timestamps();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->unique('campaign_id');
        });

        // Alertas de orçamento disparados
        Schema::create('budget_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('budget_limit_id');
            $table->tinyInteger('threshold_percent'); // 70, 90 ou 100
            $table->decimal('current_spend', 12, 2);
            $table->decimal('limit_value', 12, 2);
            $table->string('period_type', 32); // daily, monthly, total
            $table->date('period_date');
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('created_at');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->foreign('budget_limit_id')->references('id')->on('budget_limits')->onDelete('cascade');
            $table->index(['campaign_id', 'period_date']);
        });

        // Histórico de alterações em campanhas (auditoria)
        Schema::create('campaign_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 64); // created, updated, activated, paused, finished
            $table->string('field_name', 64)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->index(['campaign_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        // Parcerias locais
        Schema::create('marketing_partnerships', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->unsignedTinyInteger('type_id');
            $table->unsignedTinyInteger('status_id')->default(1);
            $table->string('contact_name', 255)->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('address', 512)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('state', 2)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('commission_percent', 5, 2)->nullable();
            $table->string('referral_code', 32)->unique()->nullable();
            $table->timestamps();
            $table->foreign('type_id')->references('id')->on('domain_partnership_type');
            $table->foreign('status_id')->references('id')->on('domain_partnership_status');
            $table->index(['type_id', 'status_id']);
            $table->index('city');
        });

        // Indicações de parcerias
        Schema::create('partnership_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partnership_id');
            $table->string('lead_id', 64);
            $table->string('lead_name', 255)->nullable();
            $table->string('lead_phone', 32)->nullable();
            $table->boolean('converted')->default(false);
            $table->decimal('contract_value', 12, 2)->nullable();
            $table->decimal('commission_value', 12, 2)->nullable();
            $table->boolean('commission_paid')->default(false);
            $table->timestamp('referred_at');
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->foreign('partnership_id')->references('id')->on('marketing_partnerships')->onDelete('cascade');
            $table->index(['partnership_id', 'converted']);
            $table->index('lead_id');
        });

        // Indicações de clientes satisfeitos
        Schema::create('customer_referrals', function (Blueprint $table) {
            $table->id();
            $table->string('referrer_customer_id', 64); // ID do cliente que indicou
            $table->string('referrer_name', 255);
            $table->string('referrer_phone', 32)->nullable();
            $table->string('referred_lead_id', 64)->nullable(); // ID do lead indicado
            $table->string('referred_name', 255)->nullable();
            $table->string('referred_phone', 32)->nullable();
            $table->string('referral_code', 32)->unique();
            $table->boolean('converted')->default(false);
            $table->decimal('contract_value', 12, 2)->nullable();
            $table->string('benefit_type', 64)->nullable(); // discount, bonus, etc.
            $table->decimal('benefit_value', 12, 2)->nullable();
            $table->boolean('benefit_applied')->default(false);
            $table->timestamp('referred_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->index(['referrer_customer_id', 'converted']);
            $table->index('referral_code');
        });

        // Configuração de programa de indicação
        Schema::create('referral_program_config', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->string('benefit_type', 64)->default('discount'); // discount, bonus, gift
            $table->decimal('referrer_benefit_value', 12, 2)->default(50);
            $table->decimal('referred_benefit_value', 12, 2)->default(0);
            $table->integer('min_contract_value')->default(0);
            $table->integer('max_referrals_per_month')->default(10);
            $table->text('terms')->nullable();
            $table->timestamps();
        });

        // Adiciona campo de aprovação obrigatória na campanha
        Schema::table('campaigns', function (Blueprint $table) {
            $table->boolean('approval_required')->default(false)->after('status_id');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_required');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });

        // Adiciona canal de indicação e parceria na tabela de canais
        $existingChannels = DB::table('marketing_channels')->pluck('name')->toArray();

        if (!in_array('Indicação', $existingChannels)) {
            DB::table('marketing_channels')->insert([
                'name' => 'Indicação',
                'status_id' => 1,
            ]);
        }

        if (!in_array('Parceria', $existingChannels)) {
            DB::table('marketing_channels')->insert([
                'name' => 'Parceria',
                'status_id' => 1,
            ]);
        }

        // Configuração padrão do programa de indicação
        DB::table('referral_program_config')->insert([
            'is_active' => true,
            'benefit_type' => 'discount',
            'referrer_benefit_value' => 50.00,
            'referred_benefit_value' => 0.00,
            'min_contract_value' => 500,
            'max_referrals_per_month' => 10,
            'terms' => 'Programa de indicação válido para clientes ativos. Benefício aplicado após primeira mensalidade paga pelo indicado.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Limite global padrão
        DB::table('budget_limits')->insert([
            'campaign_id' => null, // global
            'daily_limit' => 500.00,
            'monthly_limit' => 10000.00,
            'total_limit' => null,
            'auto_pause_enabled' => false,
            'alert_threshold_70' => 1,
            'alert_threshold_90' => 1,
            'alert_threshold_100' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['approval_required', 'approved_by', 'approved_at']);
        });

        Schema::dropIfExists('referral_program_config');
        Schema::dropIfExists('customer_referrals');
        Schema::dropIfExists('partnership_referrals');
        Schema::dropIfExists('marketing_partnerships');
        Schema::dropIfExists('campaign_audit_log');
        Schema::dropIfExists('budget_alerts');
        Schema::dropIfExists('budget_limits');
        Schema::dropIfExists('campaign_approvals');
        Schema::dropIfExists('domain_partnership_status');
        Schema::dropIfExists('domain_partnership_type');
        Schema::dropIfExists('domain_approval_status');
    }
};
