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
        Schema::create('domain_channel_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_content_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_asset_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_campaign_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_creative_type', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_landing_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        // Insere dados de dominio
        DB::table('domain_channel_status')->insert([
            ['id' => 1, 'code' => 'active', 'label' => 'Ativo'],
            ['id' => 2, 'code' => 'inactive', 'label' => 'Inativo'],
        ]);

        DB::table('domain_content_status')->insert([
            ['id' => 1, 'code' => 'draft', 'label' => 'Rascunho'],
            ['id' => 2, 'code' => 'scheduled', 'label' => 'Agendado'],
            ['id' => 3, 'code' => 'published', 'label' => 'Publicado'],
            ['id' => 4, 'code' => 'canceled', 'label' => 'Cancelado'],
        ]);

        DB::table('domain_asset_status')->insert([
            ['id' => 1, 'code' => 'draft', 'label' => 'Rascunho'],
            ['id' => 2, 'code' => 'approved', 'label' => 'Aprovado'],
            ['id' => 3, 'code' => 'published', 'label' => 'Publicado'],
        ]);

        DB::table('domain_campaign_status')->insert([
            ['id' => 1, 'code' => 'planned', 'label' => 'Planejada'],
            ['id' => 2, 'code' => 'active', 'label' => 'Ativa'],
            ['id' => 3, 'code' => 'paused', 'label' => 'Pausada'],
            ['id' => 4, 'code' => 'finished', 'label' => 'Finalizada'],
        ]);

        DB::table('domain_creative_type')->insert([
            ['id' => 1, 'code' => 'image', 'label' => 'Imagem'],
            ['id' => 2, 'code' => 'video', 'label' => 'Video'],
            ['id' => 3, 'code' => 'text', 'label' => 'Texto'],
        ]);

        DB::table('domain_landing_status')->insert([
            ['id' => 1, 'code' => 'draft', 'label' => 'Rascunho'],
            ['id' => 2, 'code' => 'published', 'label' => 'Publicada'],
            ['id' => 3, 'code' => 'archived', 'label' => 'Arquivada'],
        ]);

        // Tabelas principais
        Schema::create('marketing_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128);
            $table->unsignedTinyInteger('status_id');
            $table->foreign('status_id')->references('id')->on('domain_channel_status');
        });

        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id');
            $table->string('handle', 128);
            $table->string('profile_url', 512);
            $table->unsignedTinyInteger('status_id');
            $table->timestamps();
            $table->foreign('channel_id')->references('id')->on('marketing_channels');
            $table->foreign('status_id')->references('id')->on('domain_channel_status');
        });

        Schema::create('content_calendar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id');
            $table->string('title', 255);
            $table->dateTime('scheduled_at')->nullable();
            $table->unsignedTinyInteger('status_id');
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->timestamps();
            $table->foreign('channel_id')->references('id')->on('marketing_channels');
            $table->foreign('status_id')->references('id')->on('domain_content_status');
            $table->index(['channel_id', 'status_id', 'scheduled_at']);
        });

        Schema::create('content_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('calendar_id');
            $table->unsignedTinyInteger('asset_type_id');
            $table->string('asset_url', 512);
            $table->text('caption')->nullable();
            $table->unsignedTinyInteger('status_id');
            $table->timestamps();
            $table->foreign('calendar_id')->references('id')->on('content_calendar')->onDelete('cascade');
            $table->foreign('asset_type_id')->references('id')->on('domain_creative_type');
            $table->foreign('status_id')->references('id')->on('domain_asset_status');
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id');
            $table->string('name', 255);
            $table->string('objective', 255);
            $table->decimal('budget', 12, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('status_id');
            $table->string('external_id', 128)->nullable();
            $table->timestamps();
            $table->foreign('channel_id')->references('id')->on('marketing_channels');
            $table->foreign('status_id')->references('id')->on('domain_campaign_status');
            $table->index(['channel_id', 'status_id']);
        });

        Schema::create('ad_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->string('name', 255);
            $table->json('targeting_json');
            $table->timestamps();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
        });

        Schema::create('creatives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ad_group_id');
            $table->unsignedTinyInteger('creative_type_id');
            $table->string('headline', 255);
            $table->text('body');
            $table->string('media_url', 512)->nullable();
            $table->timestamps();
            $table->foreign('ad_group_id')->references('id')->on('ad_groups')->onDelete('cascade');
            $table->foreign('creative_type_id')->references('id')->on('domain_creative_type');
        });

        Schema::create('utm_links', function (Blueprint $table) {
            $table->id();
            $table->string('source', 128);
            $table->string('medium', 128);
            $table->string('campaign', 128);
            $table->string('content', 128)->nullable();
            $table->string('term', 128)->nullable();
            $table->timestamp('created_at');
            $table->index(['source', 'medium', 'campaign']);
        });

        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 190)->unique();
            $table->string('name', 255);
            $table->unsignedTinyInteger('status_id');
            $table->unsignedBigInteger('utm_default_id')->nullable();
            $table->timestamps();
            $table->foreign('status_id')->references('id')->on('domain_landing_status');
            $table->foreign('utm_default_id')->references('id')->on('utm_links');
        });

        Schema::create('conversion_events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('event_key', 128);
            $table->string('target_url', 512);
            $table->timestamp('created_at');
            $table->index('event_key');
        });

        Schema::create('campaign_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->date('metric_date');
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->decimal('spend', 12, 2)->default(0);
            $table->unsignedInteger('leads')->default(0);
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->unique(['campaign_id', 'metric_date']);
            $table->index(['campaign_id', 'metric_date']);
        });

        Schema::create('brand_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('type', 32);
            $table->string('file_url', 512);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['type', 'is_active']);
        });

        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->string('lead_id', 64);
            $table->string('utm_source', 128)->nullable();
            $table->string('utm_medium', 128)->nullable();
            $table->string('utm_campaign', 128)->nullable();
            $table->string('utm_content', 128)->nullable();
            $table->string('utm_term', 128)->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('landing_page_id')->nullable();
            $table->string('referrer', 512)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamp('captured_at');
            $table->timestamp('created_at');
            $table->foreign('campaign_id')->references('id')->on('campaigns');
            $table->foreign('landing_page_id')->references('id')->on('landing_pages');
            $table->index(['utm_source', 'utm_medium', 'captured_at']);
            $table->index('lead_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_sources');
        Schema::dropIfExists('brand_assets');
        Schema::dropIfExists('campaign_metrics');
        Schema::dropIfExists('conversion_events');
        Schema::dropIfExists('landing_pages');
        Schema::dropIfExists('utm_links');
        Schema::dropIfExists('creatives');
        Schema::dropIfExists('ad_groups');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('content_assets');
        Schema::dropIfExists('content_calendar');
        Schema::dropIfExists('social_accounts');
        Schema::dropIfExists('marketing_channels');
        Schema::dropIfExists('domain_landing_status');
        Schema::dropIfExists('domain_creative_type');
        Schema::dropIfExists('domain_campaign_status');
        Schema::dropIfExists('domain_asset_status');
        Schema::dropIfExists('domain_content_status');
        Schema::dropIfExists('domain_channel_status');
    }
};
