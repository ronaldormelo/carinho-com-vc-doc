<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migração para adicionar controles operacionais consolidados.
     * 
     * Melhorias baseadas em práticas de mercado:
     * - Campos de cadastro completos (CPF, data nascimento, endereço)
     * - Controle de carga de trabalho
     * - Vencimento de documentos
     * - Histórico de alocações/serviços
     * - Severidade em ocorrências
     * - Controle de afastamentos
     * - Referências profissionais
     */
    public function up(): void
    {
        // =====================================================================
        // 1. CAMPOS ADICIONAIS NO CADASTRO DE CUIDADORES
        // =====================================================================
        Schema::table('caregivers', function (Blueprint $table) {
            // Dados pessoais completos
            $table->string('cpf', 14)->nullable()->unique()->after('phone');
            $table->date('birth_date')->nullable()->after('cpf');
            
            // Endereço completo
            $table->string('address_street', 255)->nullable()->after('city');
            $table->string('address_number', 20)->nullable()->after('address_street');
            $table->string('address_complement', 100)->nullable()->after('address_number');
            $table->string('address_neighborhood', 128)->nullable()->after('address_complement');
            $table->string('address_zipcode', 10)->nullable()->after('address_neighborhood');
            $table->string('address_state', 2)->nullable()->after('address_zipcode');
            
            // Controle operacional
            $table->string('emergency_contact_name', 255)->nullable()->after('profile_summary');
            $table->string('emergency_contact_phone', 32)->nullable()->after('emergency_contact_name');
            
            // Origem do cadastro (para tracking de recrutamento)
            $table->string('recruitment_source', 64)->nullable()->after('emergency_contact_phone');
            $table->unsignedBigInteger('referred_by_caregiver_id')->nullable()->after('recruitment_source');
            
            // Índice para CPF
            $table->index('cpf');
        });

        // =====================================================================
        // 2. TABELA DE DOMÍNIO - SEVERIDADE DE OCORRÊNCIAS
        // =====================================================================
        Schema::create('domain_incident_severity', function (Blueprint $table) {
            $table->tinyInteger('id')->unsigned()->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
            $table->tinyInteger('weight')->unsigned()->default(1);
        });

        DB::table('domain_incident_severity')->insert([
            ['id' => 1, 'code' => 'low', 'label' => 'Leve', 'weight' => 1],
            ['id' => 2, 'code' => 'medium', 'label' => 'Moderada', 'weight' => 2],
            ['id' => 3, 'code' => 'high', 'label' => 'Grave', 'weight' => 3],
            ['id' => 4, 'code' => 'critical', 'label' => 'Crítica', 'weight' => 5],
        ]);

        // =====================================================================
        // 3. TABELA DE DOMÍNIO - TIPO DE AFASTAMENTO
        // =====================================================================
        Schema::create('domain_leave_type', function (Blueprint $table) {
            $table->tinyInteger('id')->unsigned()->primary();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_leave_type')->insert([
            ['id' => 1, 'code' => 'medical', 'label' => 'Atestado Médico'],
            ['id' => 2, 'code' => 'vacation', 'label' => 'Férias'],
            ['id' => 3, 'code' => 'personal', 'label' => 'Licença Pessoal'],
            ['id' => 4, 'code' => 'maternity', 'label' => 'Licença Maternidade'],
            ['id' => 5, 'code' => 'other', 'label' => 'Outro'],
        ]);

        // =====================================================================
        // 4. MELHORIAS NA TABELA DE OCORRÊNCIAS
        // =====================================================================
        Schema::table('caregiver_incidents', function (Blueprint $table) {
            // Severidade da ocorrência
            $table->tinyInteger('severity_id')->unsigned()->default(1)->after('incident_type');
            
            // Resolução/Ação tomada
            $table->text('resolution_notes')->nullable()->after('notes');
            $table->datetime('resolved_at')->nullable()->after('resolution_notes');
            $table->string('resolved_by', 255)->nullable()->after('resolved_at');
            
            // Foreign key
            $table->foreign('severity_id')
                ->references('id')
                ->on('domain_incident_severity');
            
            // Índice para consultas
            $table->index(['caregiver_id', 'severity_id']);
        });

        // =====================================================================
        // 5. VENCIMENTO DE DOCUMENTOS
        // =====================================================================
        Schema::table('caregiver_documents', function (Blueprint $table) {
            // Data de emissão e vencimento
            $table->date('issued_at')->nullable()->after('verified_at');
            $table->date('expires_at')->nullable()->after('issued_at');
            
            // Índice para alertas de vencimento
            $table->index('expires_at');
        });

        // =====================================================================
        // 6. HISTÓRICO DE ALOCAÇÕES/SERVIÇOS
        // =====================================================================
        Schema::create('caregiver_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('caregiver_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('client_id')->nullable();
            
            // Período da alocação
            $table->datetime('started_at');
            $table->datetime('ended_at')->nullable();
            
            // Horas trabalhadas (calculado ou informado)
            $table->decimal('hours_worked', 6, 2)->nullable();
            
            // Status da alocação
            $table->string('status', 32)->default('scheduled');
            
            // Notas operacionais
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->datetime('created_at');
            $table->datetime('updated_at')->nullable();
            
            // Foreign keys e índices
            $table->foreign('caregiver_id')
                ->references('id')
                ->on('caregivers');
            
            $table->index(['caregiver_id', 'started_at']);
            $table->index(['caregiver_id', 'status']);
            $table->index('service_id');
        });

        // =====================================================================
        // 7. CONTROLE DE CARGA DE TRABALHO
        // =====================================================================
        Schema::create('caregiver_workload', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('caregiver_id');
            
            // Período (semanal)
            $table->date('week_start');
            $table->date('week_end');
            
            // Horas
            $table->decimal('hours_scheduled', 6, 2)->default(0);
            $table->decimal('hours_worked', 6, 2)->default(0);
            $table->decimal('hours_overtime', 6, 2)->default(0);
            
            // Contadores
            $table->smallInteger('assignments_count')->unsigned()->default(0);
            $table->smallInteger('clients_count')->unsigned()->default(0);
            
            // Timestamps
            $table->datetime('created_at');
            $table->datetime('updated_at')->nullable();
            
            // Foreign keys e índices
            $table->foreign('caregiver_id')
                ->references('id')
                ->on('caregivers');
            
            $table->unique(['caregiver_id', 'week_start']);
            $table->index('week_start');
        });

        // =====================================================================
        // 8. CONTROLE DE AFASTAMENTOS
        // =====================================================================
        Schema::create('caregiver_leaves', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('caregiver_id');
            $table->tinyInteger('leave_type_id')->unsigned();
            
            // Período do afastamento
            $table->date('start_date');
            $table->date('end_date');
            
            // Justificativa
            $table->text('reason')->nullable();
            
            // Documento comprobatório (ex: atestado)
            $table->string('document_url', 512)->nullable();
            
            // Aprovação
            $table->boolean('approved')->default(false);
            $table->string('approved_by', 255)->nullable();
            $table->datetime('approved_at')->nullable();
            
            // Timestamps
            $table->datetime('created_at');
            $table->datetime('updated_at')->nullable();
            
            // Foreign keys e índices
            $table->foreign('caregiver_id')
                ->references('id')
                ->on('caregivers');
            
            $table->foreign('leave_type_id')
                ->references('id')
                ->on('domain_leave_type');
            
            $table->index(['caregiver_id', 'start_date', 'end_date']);
            $table->index(['start_date', 'end_date']);
        });

        // =====================================================================
        // 9. REFERÊNCIAS PROFISSIONAIS
        // =====================================================================
        Schema::create('caregiver_references', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('caregiver_id');
            
            // Dados da referência
            $table->string('name', 255);
            $table->string('phone', 32);
            $table->string('relationship', 128);
            $table->string('company', 255)->nullable();
            $table->string('position', 128)->nullable();
            
            // Verificação
            $table->boolean('verified')->default(false);
            $table->datetime('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            
            // Timestamps
            $table->datetime('created_at');
            
            // Foreign key
            $table->foreign('caregiver_id')
                ->references('id')
                ->on('caregivers');
            
            $table->index('caregiver_id');
        });

        // =====================================================================
        // 10. TABELA DE CONFIGURAÇÕES OPERACIONAIS
        // =====================================================================
        Schema::create('caregiver_settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value');
            $table->string('description', 255)->nullable();
            $table->datetime('updated_at')->nullable();
        });

        // Configurações padrão
        DB::table('caregiver_settings')->insert([
            [
                'key' => 'max_weekly_hours',
                'value' => '44',
                'description' => 'Limite máximo de horas semanais por cuidador',
                'updated_at' => now(),
            ],
            [
                'key' => 'overtime_alert_hours',
                'value' => '40',
                'description' => 'Horas para disparar alerta de hora extra',
                'updated_at' => now(),
            ],
            [
                'key' => 'document_expiry_alert_days',
                'value' => '30',
                'description' => 'Dias antes do vencimento para alertar',
                'updated_at' => now(),
            ],
            [
                'key' => 'min_rating_for_active',
                'value' => '2.5',
                'description' => 'Nota mínima para manter cuidador ativo',
                'updated_at' => now(),
            ],
            [
                'key' => 'max_incidents_for_review',
                'value' => '3',
                'description' => 'Número de ocorrências para revisão obrigatória',
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop novas tabelas
        Schema::dropIfExists('caregiver_settings');
        Schema::dropIfExists('caregiver_references');
        Schema::dropIfExists('caregiver_leaves');
        Schema::dropIfExists('caregiver_workload');
        Schema::dropIfExists('caregiver_assignments');
        
        // Remove colunas de caregiver_documents
        Schema::table('caregiver_documents', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['issued_at', 'expires_at']);
        });
        
        // Remove colunas de caregiver_incidents
        Schema::table('caregiver_incidents', function (Blueprint $table) {
            $table->dropForeign(['severity_id']);
            $table->dropIndex(['caregiver_id', 'severity_id']);
            $table->dropColumn([
                'severity_id',
                'resolution_notes',
                'resolved_at',
                'resolved_by',
            ]);
        });
        
        // Drop tabelas de domínio
        Schema::dropIfExists('domain_leave_type');
        Schema::dropIfExists('domain_incident_severity');
        
        // Remove colunas de caregivers
        Schema::table('caregivers', function (Blueprint $table) {
            $table->dropIndex(['cpf']);
            $table->dropColumn([
                'cpf',
                'birth_date',
                'address_street',
                'address_number',
                'address_complement',
                'address_neighborhood',
                'address_zipcode',
                'address_state',
                'emergency_contact_name',
                'emergency_contact_phone',
                'recruitment_source',
                'referred_by_caregiver_id',
            ]);
        });
    }
};
