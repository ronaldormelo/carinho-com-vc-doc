<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria tabela de configurações do sistema financeiro.
     * 
     * Categorias:
     * - payment: Configurações de pagamento
     * - cancellation: Políticas de cancelamento
     * - commission: Comissões e percentuais
     * - pricing: Precificação base
     * - margin: Margens e viabilidade
     * - payout: Repasses aos cuidadores
     * - fiscal: Configurações fiscais
     * - limits: Limites e alertas
     */
    public function up(): void
    {
        // Tabela de categorias de configuração
        Schema::create('setting_categories', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('name', 128);
            $table->string('description', 500)->nullable();
            $table->smallInteger('display_order')->default(0);
        });

        // Tabela de configurações
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('category_id');
            $table->string('key', 64);
            $table->string('name', 128);
            $table->string('description', 500)->nullable();
            $table->text('value');
            $table->string('value_type', 32)->default('string'); // string, integer, decimal, boolean, json
            $table->string('unit', 32)->nullable(); // %, R$, horas, dias, etc.
            $table->text('default_value')->nullable();
            $table->text('validation_rules')->nullable(); // JSON com regras de validação
            $table->boolean('is_editable')->default(true);
            $table->boolean('is_public')->default(false); // Se pode ser exibido para clientes
            $table->smallInteger('display_order')->default(0);
            $table->timestamps();
            
            $table->unique(['category_id', 'key']);
            $table->foreign('category_id')
                ->references('id')
                ->on('setting_categories')
                ->onDelete('restrict');
            
            $table->index('key');
        });

        // Tabela de histórico de alterações
        Schema::create('setting_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('setting_id');
            $table->text('old_value')->nullable();
            $table->text('new_value');
            $table->string('changed_by', 128)->nullable();
            $table->string('change_reason', 500)->nullable();
            $table->timestamp('changed_at');
            
            $table->foreign('setting_id')
                ->references('id')
                ->on('settings')
                ->onDelete('cascade');
            
            $table->index(['setting_id', 'changed_at']);
        });

        // Insere categorias padrão
        DB::table('setting_categories')->insert([
            ['id' => 1, 'code' => 'payment', 'name' => 'Pagamento', 'description' => 'Configurações de prazo e cobrança', 'display_order' => 1],
            ['id' => 2, 'code' => 'cancellation', 'name' => 'Cancelamento', 'description' => 'Políticas de cancelamento e reembolso', 'display_order' => 2],
            ['id' => 3, 'code' => 'commission', 'name' => 'Comissões', 'description' => 'Percentuais de comissão por tipo de serviço', 'display_order' => 3],
            ['id' => 4, 'code' => 'pricing', 'name' => 'Precificação', 'description' => 'Valores base e adicionais', 'display_order' => 4],
            ['id' => 5, 'code' => 'margin', 'name' => 'Margem', 'description' => 'Margens e viabilidade financeira', 'display_order' => 5],
            ['id' => 6, 'code' => 'payout', 'name' => 'Repasses', 'description' => 'Configurações de repasse aos cuidadores', 'display_order' => 6],
            ['id' => 7, 'code' => 'fiscal', 'name' => 'Fiscal', 'description' => 'Configurações fiscais e tributárias', 'display_order' => 7],
            ['id' => 8, 'code' => 'limits', 'name' => 'Limites', 'description' => 'Limites e alertas do sistema', 'display_order' => 8],
            ['id' => 9, 'code' => 'bonus', 'name' => 'Bônus', 'description' => 'Bônus por avaliação e tempo de casa', 'display_order' => 9],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_history');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('setting_categories');
    }
};
