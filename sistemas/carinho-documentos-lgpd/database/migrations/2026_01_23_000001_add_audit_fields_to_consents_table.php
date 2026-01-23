<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona campos de auditoria aprimorados na tabela de consentimentos.
 *
 * Novos campos:
 * - revocation_reason: Motivo formal da revogacao (rastreabilidade)
 * - revocation_source: Origem da revogacao (canal/sistema)
 * - ip_address: IP do dispositivo no momento do registro
 * - user_agent: User agent do navegador/app
 *
 * Justificativa:
 * - Conformidade com LGPD Art. 8 (prova do consentimento)
 * - Rastreabilidade completa para auditorias
 * - Evidencias para demonstrar compliance
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consents', function (Blueprint $table) {
            // Motivo da revogacao (quando aplicavel)
            $table->string('revocation_reason', 64)->nullable()
                ->after('revoked_at')
                ->comment('Motivo formal da revogacao do consentimento');

            // Fonte/origem da revogacao
            $table->string('revocation_source', 64)->nullable()
                ->after('revocation_reason')
                ->comment('Canal/sistema de origem da revogacao');

            // IP do dispositivo
            $table->string('ip_address', 64)->nullable()
                ->after('source')
                ->comment('Endereco IP no momento do registro');

            // User agent
            $table->string('user_agent', 512)->nullable()
                ->after('ip_address')
                ->comment('User agent do navegador/app');

            // Indice para consultas de auditoria
            $table->index(['revoked_at', 'revocation_reason'], 'idx_consents_revocation');
        });
    }

    public function down(): void
    {
        Schema::table('consents', function (Blueprint $table) {
            $table->dropIndex('idx_consents_revocation');
            $table->dropColumn([
                'revocation_reason',
                'revocation_source',
                'ip_address',
                'user_agent',
            ]);
        });
    }
};
