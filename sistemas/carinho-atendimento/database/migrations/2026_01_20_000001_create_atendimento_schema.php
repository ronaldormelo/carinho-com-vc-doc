<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_channel', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_conversation_status', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_priority', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_message_direction', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_message_status', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_agent_role', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_incident_severity', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_webhook_status', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->string('phone', 32)->unique();
            $table->string('email', 255)->nullable();
            $table->string('city', 128)->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('agents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->unsignedTinyInteger('role_id');
            $table->boolean('active')->default(true);
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();

            $table->foreign('role_id')->references('id')->on('domain_agent_role');
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('contact_id');
            $table->unsignedTinyInteger('channel_id');
            $table->unsignedTinyInteger('status_id');
            $table->unsignedTinyInteger('priority_id');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();

            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->foreign('channel_id')->references('id')->on('domain_channel');
            $table->foreign('status_id')->references('id')->on('domain_conversation_status');
            $table->foreign('priority_id')->references('id')->on('domain_priority');
            $table->foreign('assigned_to')->references('id')->on('agents');
            $table->index(['status_id', 'priority_id'], 'idx_conversations_status_priority');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedTinyInteger('direction_id');
            $table->text('body');
            $table->string('media_url', 512)->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->unsignedTinyInteger('status_id');

            $table->foreign('conversation_id')->references('id')->on('conversations');
            $table->foreign('direction_id')->references('id')->on('domain_message_direction');
            $table->foreign('status_id')->references('id')->on('domain_message_status');
            $table->index(['conversation_id', 'sent_at'], 'idx_messages_conversation_sent');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 64)->unique();
        });

        Schema::create('conversation_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('tag_id');
            $table->primary(['conversation_id', 'tag_id']);

            $table->foreign('conversation_id')->references('id')->on('conversations');
            $table->foreign('tag_id')->references('id')->on('tags');
        });

        Schema::create('message_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('template_key', 64)->unique();
            $table->text('body');
            $table->string('language', 16);
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('auto_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('trigger_key', 64);
            $table->unsignedBigInteger('template_id');
            $table->boolean('enabled')->default(true);
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();

            $table->foreign('template_id')->references('id')->on('message_templates');
        });

        Schema::create('sla_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('conversation_id');
            $table->dateTime('first_response_at')->nullable();
            $table->unsignedInteger('response_time_sec')->default(0);
            $table->dateTime('resolved_at')->nullable();

            $table->foreign('conversation_id')->references('id')->on('conversations');
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedTinyInteger('severity_id');
            $table->text('notes')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();

            $table->foreign('conversation_id')->references('id')->on('conversations');
            $table->foreign('severity_id')->references('id')->on('domain_incident_severity');
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('provider', 64);
            $table->string('event_type', 128);
            $table->json('payload_json');
            $table->dateTime('received_at');
            $table->dateTime('processed_at')->nullable();
            $table->unsignedTinyInteger('status_id');

            $table->foreign('status_id')->references('id')->on('domain_webhook_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('sla_metrics');
        Schema::dropIfExists('auto_rules');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('conversation_tags');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('domain_webhook_status');
        Schema::dropIfExists('domain_incident_severity');
        Schema::dropIfExists('domain_agent_role');
        Schema::dropIfExists('domain_message_status');
        Schema::dropIfExists('domain_message_direction');
        Schema::dropIfExists('domain_priority');
        Schema::dropIfExists('domain_conversation_status');
        Schema::dropIfExists('domain_channel');
    }
};
