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
        Schema::create('domain_doc_type', function (Blueprint $table) {
            $table->tinyInteger('id', true, true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_owner_type', function (Blueprint $table) {
            $table->tinyInteger('id', true, true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_document_status', function (Blueprint $table) {
            $table->tinyInteger('id', true, true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_signer_type', function (Blueprint $table) {
            $table->tinyInteger('id', true, true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_signature_method', function (Blueprint $table) {
            $table->tinyInteger('id', true, true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_access_action', function (Blueprint $table) {
            $table->tinyInteger('id', true, true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_request_type', function (Blueprint $table) {
            $table->tinyInteger('id', true, true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_request_status', function (Blueprint $table) {
            $table->tinyInteger('id', true, true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('domain_consent_subject_type', function (Blueprint $table) {
            $table->tinyInteger('id', true, true)->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        // Tabelas principais
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('doc_type_id');
            $table->string('version', 32);
            $table->longText('content');
            $table->boolean('active')->default(true);

            $table->foreign('doc_type_id')
                ->references('id')
                ->on('domain_doc_type')
                ->restrictOnDelete();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('owner_type_id');
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('template_id');
            $table->unsignedTinyInteger('status_id');
            $table->datetime('created_at');
            $table->datetime('updated_at')->nullable();

            $table->foreign('owner_type_id')
                ->references('id')
                ->on('domain_owner_type')
                ->restrictOnDelete();

            $table->foreign('template_id')
                ->references('id')
                ->on('document_templates')
                ->restrictOnDelete();

            $table->foreign('status_id')
                ->references('id')
                ->on('domain_document_status')
                ->restrictOnDelete();

            $table->index(['owner_type_id', 'owner_id'], 'idx_documents_owner');
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('version', 32);
            $table->string('file_url', 512);
            $table->string('checksum', 128);
            $table->datetime('created_at');

            $table->foreign('document_id')
                ->references('id')
                ->on('documents')
                ->cascadeOnDelete();
        });

        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedTinyInteger('signer_type_id');
            $table->unsignedBigInteger('signer_id');
            $table->datetime('signed_at');
            $table->unsignedTinyInteger('method_id');
            $table->string('ip_address', 64);

            $table->foreign('document_id')
                ->references('id')
                ->on('documents')
                ->cascadeOnDelete();

            $table->foreign('signer_type_id')
                ->references('id')
                ->on('domain_signer_type')
                ->restrictOnDelete();

            $table->foreign('method_id')
                ->references('id')
                ->on('domain_signature_method')
                ->restrictOnDelete();

            $table->index(['document_id', 'signed_at'], 'idx_signatures_document_time');
        });

        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('subject_type_id');
            $table->unsignedBigInteger('subject_id');
            $table->string('consent_type', 64);
            $table->datetime('granted_at');
            $table->string('source', 64);
            $table->datetime('revoked_at')->nullable();

            $table->foreign('subject_type_id')
                ->references('id')
                ->on('domain_consent_subject_type')
                ->restrictOnDelete();

            $table->index(['subject_type_id', 'subject_id', 'consent_type'], 'idx_consents_subject');
        });

        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('actor_id');
            $table->unsignedTinyInteger('action_id');
            $table->string('ip_address', 64);
            $table->datetime('created_at');

            $table->foreign('document_id')
                ->references('id')
                ->on('documents')
                ->cascadeOnDelete();

            $table->foreign('action_id')
                ->references('id')
                ->on('domain_access_action')
                ->restrictOnDelete();

            $table->index(['document_id', 'created_at'], 'idx_access_logs_document_time');
        });

        Schema::create('retention_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('doc_type_id');
            $table->unsignedInteger('retention_days');

            $table->foreign('doc_type_id')
                ->references('id')
                ->on('domain_doc_type')
                ->restrictOnDelete();
        });

        Schema::create('data_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('subject_type_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedTinyInteger('request_type_id');
            $table->unsignedTinyInteger('status_id');
            $table->datetime('requested_at');
            $table->datetime('resolved_at')->nullable();

            $table->foreign('subject_type_id')
                ->references('id')
                ->on('domain_consent_subject_type')
                ->restrictOnDelete();

            $table->foreign('request_type_id')
                ->references('id')
                ->on('domain_request_type')
                ->restrictOnDelete();

            $table->foreign('status_id')
                ->references('id')
                ->on('domain_request_status')
                ->restrictOnDelete();

            $table->index(['subject_type_id', 'subject_id'], 'idx_data_requests_subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_requests');
        Schema::dropIfExists('retention_policies');
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('consents');
        Schema::dropIfExists('signatures');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('domain_consent_subject_type');
        Schema::dropIfExists('domain_request_status');
        Schema::dropIfExists('domain_request_type');
        Schema::dropIfExists('domain_access_action');
        Schema::dropIfExists('domain_signature_method');
        Schema::dropIfExists('domain_signer_type');
        Schema::dropIfExists('domain_document_status');
        Schema::dropIfExists('domain_owner_type');
        Schema::dropIfExists('domain_doc_type');
    }
};
