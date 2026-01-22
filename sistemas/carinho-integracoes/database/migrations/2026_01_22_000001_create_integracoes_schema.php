<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabelas de dominio
        Schema::create('domain_api_key_status', function (Blueprint $table) {
            $table->tinyInteger('id', true, true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_endpoint_status', function (Blueprint $table) {
            $table->tinyInteger('id', true, true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_event_status', function (Blueprint $table) {
            $table->tinyInteger('id', true, true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_delivery_status', function (Blueprint $table) {
            $table->tinyInteger('id', true, true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_job_status', function (Blueprint $table) {
            $table->tinyInteger('id', true, true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        // Inserir dados de dominio
        DB::table('domain_api_key_status')->insert([
            ['id' => 1, 'code' => 'active', 'label' => 'Ativo'],
            ['id' => 2, 'code' => 'revoked', 'label' => 'Revogado'],
        ]);

        DB::table('domain_endpoint_status')->insert([
            ['id' => 1, 'code' => 'active', 'label' => 'Ativo'],
            ['id' => 2, 'code' => 'inactive', 'label' => 'Inativo'],
        ]);

        DB::table('domain_event_status')->insert([
            ['id' => 1, 'code' => 'pending', 'label' => 'Pendente'],
            ['id' => 2, 'code' => 'processing', 'label' => 'Processando'],
            ['id' => 3, 'code' => 'done', 'label' => 'Concluído'],
            ['id' => 4, 'code' => 'failed', 'label' => 'Falhou'],
        ]);

        DB::table('domain_delivery_status')->insert([
            ['id' => 1, 'code' => 'pending', 'label' => 'Pendente'],
            ['id' => 2, 'code' => 'sent', 'label' => 'Enviado'],
            ['id' => 3, 'code' => 'failed', 'label' => 'Falhou'],
        ]);

        DB::table('domain_job_status')->insert([
            ['id' => 1, 'code' => 'queued', 'label' => 'Na Fila'],
            ['id' => 2, 'code' => 'running', 'label' => 'Executando'],
            ['id' => 3, 'code' => 'done', 'label' => 'Concluído'],
            ['id' => 4, 'code' => 'failed', 'label' => 'Falhou'],
        ]);

        // Tabelas principais
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128);
            $table->string('key_hash', 255);
            $table->json('permissions_json');
            $table->unsignedTinyInteger('status_id');
            $table->dateTime('last_used_at')->nullable();

            $table->foreign('status_id')
                ->references('id')
                ->on('domain_api_key_status');

            $table->index('name');
        });

        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('system_name', 128);
            $table->string('url', 512);
            $table->string('secret', 255);
            $table->unsignedTinyInteger('status_id');
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();

            $table->foreign('status_id')
                ->references('id')
                ->on('domain_endpoint_status');

            $table->index('system_name');
        });

        Schema::create('integration_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 128);
            $table->string('source_system', 128);
            $table->json('payload_json');
            $table->unsignedTinyInteger('status_id');
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();

            $table->foreign('status_id')
                ->references('id')
                ->on('domain_event_status');

            $table->index(['status_id', 'created_at']);
            $table->index(['event_type', 'source_system']);
        });

        Schema::create('event_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 128);
            $table->string('target_system', 128);
            $table->json('mapping_json');
            $table->string('version', 32);

            $table->index(['event_type', 'target_system']);
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('endpoint_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedTinyInteger('status_id');
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            $table->integer('response_code')->nullable();

            $table->foreign('endpoint_id')
                ->references('id')
                ->on('webhook_endpoints');

            $table->foreign('event_id')
                ->references('id')
                ->on('integration_events');

            $table->foreign('status_id')
                ->references('id')
                ->on('domain_delivery_status');

            $table->index(['endpoint_id', 'status_id']);
        });

        Schema::create('retry_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->dateTime('next_retry_at');
            $table->unsignedInteger('attempts')->default(0);

            $table->foreign('event_id')
                ->references('id')
                ->on('integration_events');

            $table->index('next_retry_at');
        });

        Schema::create('dead_letter', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->text('reason');
            $table->dateTime('created_at');

            $table->foreign('event_id')
                ->references('id')
                ->on('integration_events');
        });

        Schema::create('sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_type', 128);
            $table->unsignedTinyInteger('status_id');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();

            $table->foreign('status_id')
                ->references('id')
                ->on('domain_job_status');

            $table->index(['job_type', 'status_id']);
        });

        Schema::create('rate_limits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->dateTime('window_start');
            $table->unsignedInteger('count')->default(0);

            $table->index(['client_id', 'window_start']);
        });

        // Tabelas de suporte Laravel
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('rate_limits');
        Schema::dropIfExists('sync_jobs');
        Schema::dropIfExists('dead_letter');
        Schema::dropIfExists('retry_queue');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('event_mappings');
        Schema::dropIfExists('integration_events');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('domain_job_status');
        Schema::dropIfExists('domain_delivery_status');
        Schema::dropIfExists('domain_event_status');
        Schema::dropIfExists('domain_endpoint_status');
        Schema::dropIfExists('domain_api_key_status');
    }
};
