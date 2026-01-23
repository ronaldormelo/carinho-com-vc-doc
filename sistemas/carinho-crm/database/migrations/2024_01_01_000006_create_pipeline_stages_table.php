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
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->integer('stage_order');
            $table->boolean('active')->default(true);

            // Index
            $table->index(['stage_order', 'active'], 'idx_pipeline_order');
        });

        // Inserir estágios padrão do pipeline
        DB::table('pipeline_stages')->insert([
            ['id' => 1, 'name' => 'Novo Lead', 'stage_order' => 1, 'active' => true],
            ['id' => 2, 'name' => 'Primeiro Contato', 'stage_order' => 2, 'active' => true],
            ['id' => 3, 'name' => 'Entendimento', 'stage_order' => 3, 'active' => true],
            ['id' => 4, 'name' => 'Proposta Enviada', 'stage_order' => 4, 'active' => true],
            ['id' => 5, 'name' => 'Negociação', 'stage_order' => 5, 'active' => true],
            ['id' => 6, 'name' => 'Fechamento', 'stage_order' => 6, 'active' => true],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
    }
};
