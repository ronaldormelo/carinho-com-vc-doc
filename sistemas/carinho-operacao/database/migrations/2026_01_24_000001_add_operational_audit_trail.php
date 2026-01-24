<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration para trilha de auditoria operacional.
 * 
 * Registra todas as alterações críticas em operações para garantir
 * rastreabilidade completa conforme práticas consolidadas de mercado.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabela de tipos de ação de auditoria
        Schema::create('domain_audit_action', function (Blueprint $table) {
            $table->tinyInteger('id')->unsigned()->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        // Dados iniciais de tipos de ação
        DB::table('domain_audit_action')->insert([
            ['id' => 1, 'code' => 'schedule_created', 'label' => 'Agendamento Criado'],
            ['id' => 2, 'code' => 'schedule_updated', 'label' => 'Agendamento Atualizado'],
            ['id' => 3, 'code' => 'schedule_canceled', 'label' => 'Agendamento Cancelado'],
            ['id' => 4, 'code' => 'checkin_performed', 'label' => 'Check-in Realizado'],
            ['id' => 5, 'code' => 'checkout_performed', 'label' => 'Check-out Realizado'],
            ['id' => 6, 'code' => 'assignment_created', 'label' => 'Alocação Criada'],
            ['id' => 7, 'code' => 'assignment_confirmed', 'label' => 'Alocação Confirmada'],
            ['id' => 8, 'code' => 'substitution_processed', 'label' => 'Substituição Processada'],
            ['id' => 9, 'code' => 'emergency_created', 'label' => 'Emergência Registrada'],
            ['id' => 10, 'code' => 'emergency_resolved', 'label' => 'Emergência Resolvida'],
            ['id' => 11, 'code' => 'emergency_escalated', 'label' => 'Emergência Escalonada'],
            ['id' => 12, 'code' => 'exception_approved', 'label' => 'Exceção Aprovada'],
            ['id' => 13, 'code' => 'exception_rejected', 'label' => 'Exceção Rejeitada'],
            ['id' => 14, 'code' => 'manual_override', 'label' => 'Alteração Manual'],
        ]);

        // Tabela principal de auditoria
        Schema::create('operational_audit_trail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('action_id')->unsigned();
            $table->string('entity_type', 64)->comment('Tipo da entidade: schedule, assignment, emergency, etc');
            $table->bigInteger('entity_id')->unsigned()->comment('ID da entidade afetada');
            $table->bigInteger('user_id')->unsigned()->nullable()->comment('Usuário que realizou a ação');
            $table->string('user_type', 32)->default('system')->comment('Tipo: system, operator, supervisor');
            $table->json('old_values')->nullable()->comment('Valores anteriores');
            $table->json('new_values')->nullable()->comment('Novos valores');
            $table->string('reason', 500)->nullable()->comment('Motivo/justificativa');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('action_id')
                ->references('id')
                ->on('domain_audit_action');

            $table->index(['entity_type', 'entity_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });

        // Tabela de exceções operacionais com workflow de aprovação
        Schema::create('operational_exceptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('exception_type', 64)->comment('Tipo: late_checkin, early_checkout, schedule_change, etc');
            $table->string('entity_type', 64);
            $table->bigInteger('entity_id')->unsigned();
            $table->bigInteger('requested_by')->unsigned()->nullable();
            $table->text('description');
            $table->string('status', 32)->default('pending')->comment('pending, approved, rejected');
            $table->bigInteger('approved_by')->unsigned()->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();

            $table->index(['status', 'requested_at']);
            $table->index(['entity_type', 'entity_id']);
        });

        // Tabela de banco de cuidadores backup por região
        Schema::create('backup_caregivers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('caregiver_id')->unsigned();
            $table->string('region_code', 32);
            $table->tinyInteger('priority')->unsigned()->default(1)->comment('1=Alta, 2=Media, 3=Baixa');
            $table->boolean('is_available')->default(true);
            $table->time('available_from')->nullable();
            $table->time('available_until')->nullable();
            $table->json('service_types')->nullable()->comment('Tipos de serviço que aceita');
            $table->timestamp('last_assignment_at')->nullable();
            $table->timestamps();

            $table->index(['region_code', 'is_available', 'priority']);
            $table->unique(['caregiver_id', 'region_code']);
        });

        // Tabela de métricas de SLA
        Schema::create('sla_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('metric_date');
            $table->string('metric_type', 64)->comment('allocation_time, checkin_punctuality, substitution_rate, etc');
            $table->string('dimension', 64)->nullable()->comment('region, caregiver, service_type');
            $table->string('dimension_value', 64)->nullable();
            $table->decimal('target_value', 10, 2);
            $table->decimal('actual_value', 10, 2);
            $table->boolean('target_met')->default(false);
            $table->integer('sample_size')->default(0);
            $table->timestamps();

            $table->unique(['metric_date', 'metric_type', 'dimension', 'dimension_value'], 'sla_metrics_unique');
            $table->index(['metric_date', 'target_met']);
        });

        // Tabela de alertas de SLA
        Schema::create('sla_alerts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sla_metric_id')->unsigned()->nullable();
            $table->string('alert_type', 64)->comment('threshold_breach, trend_warning, critical');
            $table->string('metric_type', 64);
            $table->text('message');
            $table->string('severity', 16)->default('warning')->comment('info, warning, critical');
            $table->boolean('is_acknowledged')->default(false);
            $table->bigInteger('acknowledged_by')->unsigned()->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->foreign('sla_metric_id')
                ->references('id')
                ->on('sla_metrics');

            $table->index(['is_acknowledged', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_alerts');
        Schema::dropIfExists('sla_metrics');
        Schema::dropIfExists('backup_caregivers');
        Schema::dropIfExists('operational_exceptions');
        Schema::dropIfExists('operational_audit_trail');
        Schema::dropIfExists('domain_audit_action');
    }
};
