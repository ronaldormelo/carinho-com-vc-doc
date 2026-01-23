<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Novas tabelas de domínio
        Schema::create('domain_support_level', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
            $table->unsignedInteger('escalation_minutes')->default(30);
        });

        Schema::create('domain_loss_reason', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('label', 128);
        });

        Schema::create('domain_incident_category', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('label', 128);
        });

        Schema::create('domain_action_type', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('label', 128);
        });

        // Adicionar colunas à tabela conversations
        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedTinyInteger('support_level_id')->default(1)->after('priority_id');
            $table->unsignedTinyInteger('loss_reason_id')->nullable()->after('assigned_to');
            $table->text('loss_notes')->nullable()->after('loss_reason_id');
        });

        // Adicionar foreign keys após criar tabelas de domínio
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreign('support_level_id')->references('id')->on('domain_support_level');
            $table->foreign('loss_reason_id')->references('id')->on('domain_loss_reason');
        });

        // Adicionar colunas à tabela incidents
        Schema::table('incidents', function (Blueprint $table) {
            $table->unsignedTinyInteger('category_id')->default(9)->after('severity_id');
            $table->text('resolution')->nullable()->after('notes');
            $table->dateTime('resolved_at')->nullable()->after('resolution');
            $table->unsignedBigInteger('resolved_by')->nullable()->after('resolved_at');

            $table->foreign('category_id')->references('id')->on('domain_incident_category');
            $table->foreign('resolved_by')->references('id')->on('agents');
            $table->index(['category_id', 'severity_id'], 'idx_incidents_category');
        });

        // Histórico de ações nas conversas
        Schema::create('conversation_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedTinyInteger('action_type_id');
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->string('old_value', 255)->nullable();
            $table->string('new_value', 255)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('created_at');

            $table->foreign('conversation_id')->references('id')->on('conversations');
            $table->foreign('action_type_id')->references('id')->on('domain_action_type');
            $table->foreign('agent_id')->references('id')->on('agents');
            $table->index(['conversation_id', 'created_at'], 'idx_conversation_history_conversation');
        });

        // Metas de SLA por prioridade
        Schema::create('sla_targets', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->autoIncrement();
            $table->unsignedTinyInteger('priority_id');
            $table->unsignedInteger('first_response_minutes');
            $table->unsignedInteger('resolution_minutes');

            $table->foreign('priority_id')->references('id')->on('domain_priority');
        });

        // Checklist de triagem
        Schema::create('triage_checklist', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('item_key', 64);
            $table->string('item_label', 255);
            $table->unsignedTinyInteger('item_order')->default(0);
            $table->boolean('required')->default(true);
            $table->boolean('active')->default(true);
        });

        // Respostas da triagem por conversa
        Schema::create('conversation_triage', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('checklist_id');
            $table->text('response')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();

            $table->foreign('conversation_id')->references('id')->on('conversations');
            $table->foreign('checklist_id')->references('id')->on('triage_checklist');
            $table->foreign('completed_by')->references('id')->on('agents');
            $table->index('conversation_id', 'idx_conversation_triage_conversation');
        });

        // Feriados para controle de horário comercial
        Schema::create('holidays', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date')->unique();
            $table->string('description', 128);
            $table->boolean('year_recurring')->default(false);

            $table->index('date', 'idx_holidays_date');
        });

        // Pesquisa de satisfação
        Schema::create('satisfaction_surveys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('feedback')->nullable();
            $table->dateTime('sent_at');
            $table->dateTime('responded_at')->nullable();

            $table->foreign('conversation_id')->references('id')->on('conversations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satisfaction_surveys');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('conversation_triage');
        Schema::dropIfExists('triage_checklist');
        Schema::dropIfExists('sla_targets');
        Schema::dropIfExists('conversation_history');

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['resolved_by']);
            $table->dropIndex('idx_incidents_category');
            $table->dropColumn(['category_id', 'resolution', 'resolved_at', 'resolved_by']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['support_level_id']);
            $table->dropForeign(['loss_reason_id']);
            $table->dropColumn(['support_level_id', 'loss_reason_id', 'loss_notes']);
        });

        Schema::dropIfExists('domain_action_type');
        Schema::dropIfExists('domain_incident_category');
        Schema::dropIfExists('domain_loss_reason');
        Schema::dropIfExists('domain_support_level');
    }
};
