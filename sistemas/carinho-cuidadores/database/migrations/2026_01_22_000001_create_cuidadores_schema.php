<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabelas de dominio
        Schema::create('domain_caregiver_status', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_document_type', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_document_status', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_care_type', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_skill_level', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_contract_status', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        // Tabela principal de cuidadores
        Schema::create('caregivers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->string('phone', 32);
            $table->string('email', 255)->nullable();
            $table->string('city', 128);
            $table->unsignedTinyInteger('status_id');
            $table->unsignedInteger('experience_years')->default(0);
            $table->text('profile_summary')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();

            $table->foreign('status_id')
                ->references('id')
                ->on('domain_caregiver_status');

            $table->index(['phone', 'status_id'], 'idx_caregivers_phone_status');
            $table->index('city', 'idx_caregivers_city');
        });

        // Documentos dos cuidadores
        Schema::create('caregiver_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('caregiver_id');
            $table->unsignedTinyInteger('doc_type_id');
            $table->string('file_url', 512);
            $table->unsignedTinyInteger('status_id');
            $table->dateTime('verified_at')->nullable();

            $table->foreign('caregiver_id')
                ->references('id')
                ->on('caregivers')
                ->cascadeOnDelete();
            $table->foreign('doc_type_id')
                ->references('id')
                ->on('domain_document_type');
            $table->foreign('status_id')
                ->references('id')
                ->on('domain_document_status');

            $table->index(['caregiver_id', 'status_id'], 'idx_caregiver_documents_status');
        });

        // Habilidades/tipos de cuidado
        Schema::create('caregiver_skills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('caregiver_id');
            $table->unsignedTinyInteger('care_type_id');
            $table->unsignedTinyInteger('level_id');

            $table->foreign('caregiver_id')
                ->references('id')
                ->on('caregivers')
                ->cascadeOnDelete();
            $table->foreign('care_type_id')
                ->references('id')
                ->on('domain_care_type');
            $table->foreign('level_id')
                ->references('id')
                ->on('domain_skill_level');

            $table->unique(['caregiver_id', 'care_type_id'], 'uk_caregiver_skills');
        });

        // Disponibilidade
        Schema::create('caregiver_availability', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('caregiver_id');
            $table->unsignedTinyInteger('day_of_week'); // 0=Dom, 6=Sab
            $table->time('start_time');
            $table->time('end_time');

            $table->foreign('caregiver_id')
                ->references('id')
                ->on('caregivers')
                ->cascadeOnDelete();

            $table->index(['caregiver_id', 'day_of_week'], 'idx_caregiver_availability_day');
        });

        // Regioes de atuacao
        Schema::create('caregiver_regions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('caregiver_id');
            $table->string('city', 128);
            $table->string('neighborhood', 128)->nullable();

            $table->foreign('caregiver_id')
                ->references('id')
                ->on('caregivers')
                ->cascadeOnDelete();

            $table->index('city', 'idx_caregiver_regions_city');
        });

        // Contratos
        Schema::create('caregiver_contracts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('caregiver_id');
            $table->unsignedBigInteger('contract_id'); // ID externo no sistema de documentos
            $table->unsignedTinyInteger('status_id');
            $table->dateTime('signed_at')->nullable();

            $table->foreign('caregiver_id')
                ->references('id')
                ->on('caregivers')
                ->cascadeOnDelete();
            $table->foreign('status_id')
                ->references('id')
                ->on('domain_contract_status');

            $table->index(['caregiver_id', 'status_id'], 'idx_caregiver_contracts_status');
        });

        // Avaliacoes
        Schema::create('caregiver_ratings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('caregiver_id');
            $table->unsignedBigInteger('service_id'); // ID do servico no sistema de operacao
            $table->unsignedTinyInteger('score'); // 1-5
            $table->text('comment')->nullable();
            $table->dateTime('created_at');

            $table->foreign('caregiver_id')
                ->references('id')
                ->on('caregivers')
                ->cascadeOnDelete();

            $table->unique(['caregiver_id', 'service_id'], 'uk_caregiver_rating_service');
            $table->index(['caregiver_id', 'created_at'], 'idx_caregiver_ratings_date');
        });

        // Ocorrencias/Incidentes
        Schema::create('caregiver_incidents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('caregiver_id');
            $table->unsignedBigInteger('service_id');
            $table->string('incident_type', 128);
            $table->text('notes');
            $table->dateTime('occurred_at');

            $table->foreign('caregiver_id')
                ->references('id')
                ->on('caregivers')
                ->cascadeOnDelete();

            $table->index(['caregiver_id', 'occurred_at'], 'idx_caregiver_incidents_date');
            $table->index('incident_type', 'idx_caregiver_incidents_type');
        });

        // Treinamentos
        Schema::create('caregiver_training', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('caregiver_id');
            $table->string('course_name', 255);
            $table->dateTime('completed_at')->nullable();

            $table->foreign('caregiver_id')
                ->references('id')
                ->on('caregivers')
                ->cascadeOnDelete();
        });

        // Historico de status
        Schema::create('caregiver_status_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('caregiver_id');
            $table->unsignedTinyInteger('status_id');
            $table->dateTime('changed_at');

            $table->foreign('caregiver_id')
                ->references('id')
                ->on('caregivers')
                ->cascadeOnDelete();
            $table->foreign('status_id')
                ->references('id')
                ->on('domain_caregiver_status');

            $table->index(['caregiver_id', 'changed_at'], 'idx_caregiver_status_history_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caregiver_status_history');
        Schema::dropIfExists('caregiver_training');
        Schema::dropIfExists('caregiver_incidents');
        Schema::dropIfExists('caregiver_ratings');
        Schema::dropIfExists('caregiver_contracts');
        Schema::dropIfExists('caregiver_regions');
        Schema::dropIfExists('caregiver_availability');
        Schema::dropIfExists('caregiver_skills');
        Schema::dropIfExists('caregiver_documents');
        Schema::dropIfExists('caregivers');
        Schema::dropIfExists('domain_contract_status');
        Schema::dropIfExists('domain_skill_level');
        Schema::dropIfExists('domain_care_type');
        Schema::dropIfExists('domain_document_status');
        Schema::dropIfExists('domain_document_type');
        Schema::dropIfExists('domain_caregiver_status');
    }
};
