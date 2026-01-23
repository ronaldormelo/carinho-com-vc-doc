<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabelas de dominio
        Schema::create('domain_service_type', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_urgency_level', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_service_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_assignment_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_schedule_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_checklist_type', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_check_type', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_notification_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_emergency_severity', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        // Tabela principal de solicitacoes de servico
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedTinyInteger('service_type_id');
            $table->unsignedTinyInteger('urgency_id');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('status_id');
            $table->timestamps();

            $table->foreign('service_type_id')
                ->references('id')
                ->on('domain_service_type');
            $table->foreign('urgency_id')
                ->references('id')
                ->on('domain_urgency_level');
            $table->foreign('status_id')
                ->references('id')
                ->on('domain_service_status');

            $table->index(['status_id', 'start_date']);
            $table->index('client_id');
        });

        // Alocacoes de cuidadores
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_request_id');
            $table->unsignedBigInteger('caregiver_id');
            $table->unsignedTinyInteger('status_id');
            $table->dateTime('assigned_at');

            $table->foreign('service_request_id')
                ->references('id')
                ->on('service_requests');
            $table->foreign('status_id')
                ->references('id')
                ->on('domain_assignment_status');

            $table->index('caregiver_id');
            $table->index(['service_request_id', 'status_id']);
        });

        // Agendamentos
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('caregiver_id');
            $table->unsignedBigInteger('client_id');
            $table->date('shift_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('status_id');

            $table->foreign('assignment_id')
                ->references('id')
                ->on('assignments');
            $table->foreign('status_id')
                ->references('id')
                ->on('domain_schedule_status');

            $table->index(['caregiver_id', 'shift_date']);
            $table->index(['client_id', 'shift_date']);
            $table->index(['shift_date', 'status_id']);
        });

        // Checklists
        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_request_id');
            $table->unsignedTinyInteger('checklist_type_id');
            $table->json('template_json');

            $table->foreign('service_request_id')
                ->references('id')
                ->on('service_requests');
            $table->foreign('checklist_type_id')
                ->references('id')
                ->on('domain_checklist_type');
        });

        // Entradas de checklist
        Schema::create('checklist_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('checklist_id');
            $table->string('item_key', 128);
            $table->boolean('completed')->default(false);
            $table->text('notes')->nullable();

            $table->foreign('checklist_id')
                ->references('id')
                ->on('checklists')
                ->onDelete('cascade');
        });

        // Check-ins
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedTinyInteger('check_type_id');
            $table->dateTime('timestamp');
            $table->string('location', 255)->nullable();

            $table->foreign('schedule_id')
                ->references('id')
                ->on('schedules');
            $table->foreign('check_type_id')
                ->references('id')
                ->on('domain_check_type');

            $table->index(['schedule_id', 'check_type_id']);
        });

        // Logs de servico
        Schema::create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->json('activities_json');
            $table->text('notes')->nullable();
            $table->dateTime('created_at');

            $table->foreign('schedule_id')
                ->references('id')
                ->on('schedules');
        });

        // Substituicoes
        Schema::create('substitutions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('old_caregiver_id');
            $table->unsignedBigInteger('new_caregiver_id');
            $table->string('reason', 255);
            $table->dateTime('created_at');

            $table->foreign('assignment_id')
                ->references('id')
                ->on('assignments');

            $table->index('old_caregiver_id');
            $table->index('new_caregiver_id');
        });

        // Notificacoes
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->string('notif_type', 64);
            $table->unsignedTinyInteger('status_id');
            $table->dateTime('sent_at')->nullable();

            $table->foreign('status_id')
                ->references('id')
                ->on('domain_notification_status');

            $table->index('client_id');
            $table->index(['notif_type', 'status_id']);
        });

        // Emergencias
        Schema::create('emergencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_request_id');
            $table->unsignedTinyInteger('severity_id');
            $table->text('description');
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('service_request_id')
                ->references('id')
                ->on('service_requests');
            $table->foreign('severity_id')
                ->references('id')
                ->on('domain_emergency_severity');

            $table->index(['severity_id', 'resolved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergencies');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('substitutions');
        Schema::dropIfExists('service_logs');
        Schema::dropIfExists('checkins');
        Schema::dropIfExists('checklist_entries');
        Schema::dropIfExists('checklists');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('domain_emergency_severity');
        Schema::dropIfExists('domain_notification_status');
        Schema::dropIfExists('domain_check_type');
        Schema::dropIfExists('domain_checklist_type');
        Schema::dropIfExists('domain_schedule_status');
        Schema::dropIfExists('domain_assignment_status');
        Schema::dropIfExists('domain_service_status');
        Schema::dropIfExists('domain_urgency_level');
        Schema::dropIfExists('domain_service_type');
    }
};
