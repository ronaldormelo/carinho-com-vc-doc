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
        // Tabela de níveis de urgência
        Schema::create('domain_urgency_level', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_urgency_level')->insert([
            ['id' => 1, 'code' => 'hoje', 'label' => 'Hoje'],
            ['id' => 2, 'code' => 'semana', 'label' => 'Semana'],
            ['id' => 3, 'code' => 'sem_data', 'label' => 'Sem data'],
        ]);

        // Tabela de tipos de serviço
        Schema::create('domain_service_type', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_service_type')->insert([
            ['id' => 1, 'code' => 'horista', 'label' => 'Horista'],
            ['id' => 2, 'code' => 'diario', 'label' => 'Diário'],
            ['id' => 3, 'code' => 'mensal', 'label' => 'Mensal'],
        ]);

        // Tabela de status de lead
        Schema::create('domain_lead_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_lead_status')->insert([
            ['id' => 1, 'code' => 'new', 'label' => 'Novo'],
            ['id' => 2, 'code' => 'triage', 'label' => 'Triagem'],
            ['id' => 3, 'code' => 'proposal', 'label' => 'Proposta'],
            ['id' => 4, 'code' => 'active', 'label' => 'Ativo'],
            ['id' => 5, 'code' => 'lost', 'label' => 'Perdido'],
        ]);

        // Tabela de status de deal
        Schema::create('domain_deal_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_deal_status')->insert([
            ['id' => 1, 'code' => 'open', 'label' => 'Aberto'],
            ['id' => 2, 'code' => 'won', 'label' => 'Ganho'],
            ['id' => 3, 'code' => 'lost', 'label' => 'Perdido'],
        ]);

        // Tabela de status de contrato
        Schema::create('domain_contract_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_contract_status')->insert([
            ['id' => 1, 'code' => 'draft', 'label' => 'Rascunho'],
            ['id' => 2, 'code' => 'signed', 'label' => 'Assinado'],
            ['id' => 3, 'code' => 'active', 'label' => 'Ativo'],
            ['id' => 4, 'code' => 'closed', 'label' => 'Encerrado'],
        ]);

        // Tabela de canais de interação
        Schema::create('domain_interaction_channel', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_interaction_channel')->insert([
            ['id' => 1, 'code' => 'whatsapp', 'label' => 'WhatsApp'],
            ['id' => 2, 'code' => 'email', 'label' => 'E-mail'],
            ['id' => 3, 'code' => 'phone', 'label' => 'Telefone'],
        ]);

        // Tabela de tipos de paciente
        Schema::create('domain_patient_type', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_patient_type')->insert([
            ['id' => 1, 'code' => 'idoso', 'label' => 'Idoso'],
            ['id' => 2, 'code' => 'pcd', 'label' => 'PCD'],
            ['id' => 3, 'code' => 'tea', 'label' => 'TEA'],
            ['id' => 4, 'code' => 'pos_operatorio', 'label' => 'Pós-operatório'],
        ]);

        // Tabela de status de tarefa
        Schema::create('domain_task_status', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        DB::table('domain_task_status')->insert([
            ['id' => 1, 'code' => 'open', 'label' => 'Aberta'],
            ['id' => 2, 'code' => 'done', 'label' => 'Concluída'],
            ['id' => 3, 'code' => 'canceled', 'label' => 'Cancelada'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_task_status');
        Schema::dropIfExists('domain_patient_type');
        Schema::dropIfExists('domain_interaction_channel');
        Schema::dropIfExists('domain_contract_status');
        Schema::dropIfExists('domain_deal_status');
        Schema::dropIfExists('domain_lead_status');
        Schema::dropIfExists('domain_service_type');
        Schema::dropIfExists('domain_urgency_level');
    }
};
