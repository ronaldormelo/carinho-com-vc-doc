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
        // =================================================================
        // Tabelas de Dominio (Valores de Referencia)
        // =================================================================

        Schema::create('domain_page_status', function (Blueprint $table) {
            $table->tinyInteger('id', unsigned: true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_page_status')->insert([
            ['id' => 1, 'code' => 'draft', 'label' => 'Rascunho'],
            ['id' => 2, 'code' => 'published', 'label' => 'Publicado'],
            ['id' => 3, 'code' => 'archived', 'label' => 'Arquivado'],
        ]);

        Schema::create('domain_form_target', function (Blueprint $table) {
            $table->tinyInteger('id', unsigned: true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_form_target')->insert([
            ['id' => 1, 'code' => 'cliente', 'label' => 'Cliente'],
            ['id' => 2, 'code' => 'cuidador', 'label' => 'Cuidador'],
        ]);

        Schema::create('domain_urgency_level', function (Blueprint $table) {
            $table->tinyInteger('id', unsigned: true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
            $table->tinyInteger('priority')->default(1);
        });

        DB::table('domain_urgency_level')->insert([
            ['id' => 1, 'code' => 'hoje', 'label' => 'Hoje', 'priority' => 1],
            ['id' => 2, 'code' => 'semana', 'label' => 'Esta semana', 'priority' => 2],
            ['id' => 3, 'code' => 'sem_data', 'label' => 'Sem data definida', 'priority' => 3],
        ]);

        Schema::create('domain_service_type', function (Blueprint $table) {
            $table->tinyInteger('id', unsigned: true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
            $table->text('description')->nullable();
        });

        DB::table('domain_service_type')->insert([
            ['id' => 1, 'code' => 'horista', 'label' => 'Horista', 'description' => 'Atendimento por hora para demandas pontuais'],
            ['id' => 2, 'code' => 'diario', 'label' => 'Diario', 'description' => 'Turnos diurnos ou noturnos recorrentes'],
            ['id' => 3, 'code' => 'mensal', 'label' => 'Mensal', 'description' => 'Escala fixa com continuidade'],
        ]);

        Schema::create('domain_legal_doc_type', function (Blueprint $table) {
            $table->tinyInteger('id', unsigned: true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_legal_doc_type')->insert([
            ['id' => 1, 'code' => 'privacy', 'label' => 'Politica de Privacidade'],
            ['id' => 2, 'code' => 'terms', 'label' => 'Termos de Uso'],
            ['id' => 3, 'code' => 'cancellation', 'label' => 'Politica de Cancelamento'],
            ['id' => 4, 'code' => 'emergency', 'label' => 'Politica de Emergencias'],
            ['id' => 5, 'code' => 'payment', 'label' => 'Politica de Pagamento'],
            ['id' => 6, 'code' => 'caregiver_terms', 'label' => 'Termos do Cuidador'],
        ]);

        // =================================================================
        // Paginas e Conteudo
        // =================================================================

        Schema::create('site_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 190)->unique();
            $table->string('title');
            $table->tinyInteger('status_id', unsigned: true);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 512)->nullable();
            $table->string('seo_keywords')->nullable();
            $table->json('content_json');
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('status_id')->references('id')->on('domain_page_status');
            $table->index('status_id');
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('site_pages');
            $table->string('type', 64);
            $table->json('content_json');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['page_id', 'sort_order']);
        });

        // =================================================================
        // Media e Assets
        // =================================================================

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('mime_type', 128);
            $table->unsignedInteger('size_bytes');
            $table->string('storage_path', 512);
            $table->string('checksum', 128);
            $table->string('alt_text')->nullable();
            $table->timestamps();
        });

        // =================================================================
        // Formularios de Lead
        // =================================================================

        Schema::create('lead_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('target_type_id', unsigned: true);
            $table->json('fields_json');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('target_type_id')->references('id')->on('domain_form_target');
        });

        // =================================================================
        // UTM e Campanhas
        // =================================================================

        Schema::create('utm_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('source', 128);
            $table->string('medium', 128);
            $table->string('campaign', 128);
            $table->string('content', 128)->nullable();
            $table->string('term', 128)->nullable();
            $table->timestamps();

            $table->index(['source', 'campaign']);
            $table->unique(['source', 'medium', 'campaign', 'content', 'term'], 'utm_unique');
        });

        // =================================================================
        // Submissoes de Formulario
        // =================================================================

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('lead_forms');
            $table->foreignId('utm_id')->nullable()->constrained('utm_campaigns');
            $table->string('name');
            $table->string('phone', 32);
            $table->string('email')->nullable();
            $table->string('city', 128);
            $table->tinyInteger('urgency_id', unsigned: true);
            $table->tinyInteger('service_type_id', unsigned: true);
            $table->dateTime('consent_at')->nullable();
            $table->json('payload_json');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->boolean('synced_to_crm')->default(false);
            $table->timestamp('created_at');

            $table->foreign('urgency_id')->references('id')->on('domain_urgency_level');
            $table->foreign('service_type_id')->references('id')->on('domain_service_type');

            $table->index(['phone', 'created_at']);
            $table->index('synced_to_crm');
        });

        // =================================================================
        // Documentos Legais
        // =================================================================

        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('doc_type_id', unsigned: true);
            $table->string('version', 32);
            $table->longText('content');
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->foreign('doc_type_id')->references('id')->on('domain_legal_doc_type');
            $table->index(['doc_type_id', 'published_at']);
        });

        // =================================================================
        // Configuracoes do Site
        // =================================================================

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 190)->unique();
            $table->text('setting_value');
            $table->string('description')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // =================================================================
        // Redirects
        // =================================================================

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_url', 512);
            $table->integer('status_code')->default(301);
            $table->timestamps();
        });

        // =================================================================
        // FAQ
        // =================================================================

        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 190)->unique();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('faq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('faq_categories');
            $table->string('question');
            $table->text('answer');
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'sort_order']);
        });

        // =================================================================
        // Depoimentos / Testimonials
        // =================================================================

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role')->nullable(); // Ex: "Filha de paciente"
            $table->text('content');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('avatar_url')->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // =================================================================
        // Logs de Acesso
        // =================================================================

        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('utm_source', 128)->nullable();
            $table->string('utm_medium', 128)->nullable();
            $table->string('utm_campaign', 128)->nullable();
            $table->string('referrer', 512)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at');

            $table->index(['created_at', 'path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('faq_items');
        Schema::dropIfExists('faq_categories');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('legal_documents');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('utm_campaigns');
        Schema::dropIfExists('lead_forms');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('site_pages');
        Schema::dropIfExists('domain_legal_doc_type');
        Schema::dropIfExists('domain_service_type');
        Schema::dropIfExists('domain_urgency_level');
        Schema::dropIfExists('domain_form_target');
        Schema::dropIfExists('domain_page_status');
    }
};
