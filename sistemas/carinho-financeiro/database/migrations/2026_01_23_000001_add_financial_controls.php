<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migration para adicionar controles financeiros avançados.
     * 
     * Adiciona:
     * - Tabelas de domínio para tipos de transação e categorias
     * - Tabela de transações financeiras (cash_transactions)
     * - Tabela de contas a pagar (payables)
     * - Tabela de provisões (provisions)
     * - Tabela de aprovações (approvals)
     */
    public function up(): void
    {
        if (!Schema::hasTable('domain_transaction_type')) {
            Schema::create('domain_transaction_type', function (Blueprint $table) {
                $table->tinyInteger('id', true, true)->primary();
                $table->string('code', 32)->unique();
                $table->string('label', 64);
            });

            DB::table('domain_transaction_type')->insert([
                ['id' => 1, 'code' => 'receipt', 'label' => 'Recebimento'],
                ['id' => 2, 'code' => 'payment', 'label' => 'Pagamento'],
                ['id' => 3, 'code' => 'transfer', 'label' => 'Transferência'],
                ['id' => 4, 'code' => 'adjustment', 'label' => 'Ajuste'],
                ['id' => 5, 'code' => 'fee', 'label' => 'Taxa'],
                ['id' => 6, 'code' => 'refund', 'label' => 'Reembolso'],
            ]);
        }

        if (!Schema::hasTable('domain_financial_category')) {
            Schema::create('domain_financial_category', function (Blueprint $table) {
                $table->tinyInteger('id', true, true)->primary();
                $table->string('code', 32)->unique();
                $table->string('label', 64);
                $table->enum('type', ['revenue', 'expense', 'both'])->default('both');
            });

            DB::table('domain_financial_category')->insert([
                ['id' => 1, 'code' => 'service_revenue', 'label' => 'Receita de Serviços', 'type' => 'revenue'],
                ['id' => 2, 'code' => 'cancellation_fee', 'label' => 'Taxa de Cancelamento', 'type' => 'revenue'],
                ['id' => 3, 'code' => 'late_fee', 'label' => 'Juros e Multas', 'type' => 'revenue'],
                ['id' => 4, 'code' => 'other_revenue', 'label' => 'Outras Receitas', 'type' => 'revenue'],
                ['id' => 10, 'code' => 'caregiver_payout', 'label' => 'Repasse Cuidadores', 'type' => 'expense'],
                ['id' => 11, 'code' => 'gateway_fee', 'label' => 'Taxa Gateway', 'type' => 'expense'],
                ['id' => 12, 'code' => 'transfer_fee', 'label' => 'Taxa Transferência', 'type' => 'expense'],
                ['id' => 13, 'code' => 'refund_expense', 'label' => 'Reembolso Cliente', 'type' => 'expense'],
                ['id' => 14, 'code' => 'operational', 'label' => 'Despesa Operacional', 'type' => 'expense'],
                ['id' => 15, 'code' => 'administrative', 'label' => 'Despesa Administrativa', 'type' => 'expense'],
                ['id' => 16, 'code' => 'tax', 'label' => 'Impostos e Tributos', 'type' => 'expense'],
                ['id' => 17, 'code' => 'other_expense', 'label' => 'Outras Despesas', 'type' => 'expense'],
            ]);
        }

        if (!Schema::hasTable('domain_approval_status')) {
            Schema::create('domain_approval_status', function (Blueprint $table) {
                $table->tinyInteger('id', true, true)->primary();
                $table->string('code', 32)->unique();
                $table->string('label', 64);
            });

            DB::table('domain_approval_status')->insert([
                ['id' => 1, 'code' => 'pending', 'label' => 'Pendente'],
                ['id' => 2, 'code' => 'approved', 'label' => 'Aprovado'],
                ['id' => 3, 'code' => 'rejected', 'label' => 'Rejeitado'],
                ['id' => 4, 'code' => 'auto_approved', 'label' => 'Aprovado Automático'],
            ]);
        }

        if (!Schema::hasTable('domain_payable_status')) {
            Schema::create('domain_payable_status', function (Blueprint $table) {
                $table->tinyInteger('id', true, true)->primary();
                $table->string('code', 32)->unique();
                $table->string('label', 64);
            });

            DB::table('domain_payable_status')->insert([
                ['id' => 1, 'code' => 'open', 'label' => 'Em Aberto'],
                ['id' => 2, 'code' => 'scheduled', 'label' => 'Agendado'],
                ['id' => 3, 'code' => 'paid', 'label' => 'Pago'],
                ['id' => 4, 'code' => 'canceled', 'label' => 'Cancelado'],
            ]);
        }

        // bank_accounts só existe em 2026_09_07_000002; a FK entra em 000003.
        if (!Schema::hasTable('cash_transactions')) {
            Schema::create('cash_transactions', function (Blueprint $table) {
                $table->id();
                $table->date('transaction_date');
                $table->date('competence_date')->nullable()->comment('Data de competência contábil');
                $table->unsignedTinyInteger('type_id');
                $table->unsignedTinyInteger('category_id');
                $table->string('description', 255);
                $table->decimal('amount', 12, 2);
                $table->enum('direction', ['in', 'out'])->comment('in=entrada, out=saída');
                $table->string('reference_type', 64)->nullable()->comment('invoice, payment, payout, payable');
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->unsignedBigInteger('bank_account_id')->nullable();
                $table->string('external_reference', 128)->nullable()->comment('ID externo (Stripe, etc)');
                $table->text('notes')->nullable();
                $table->string('created_by', 128)->nullable();
                $table->timestamps();

                $table->foreign('type_id')
                    ->references('id')
                    ->on('domain_transaction_type');

                $table->foreign('category_id')
                    ->references('id')
                    ->on('domain_financial_category');

                $table->index(['transaction_date', 'direction']);
                $table->index(['competence_date', 'category_id']);
                $table->index(['reference_type', 'reference_id']);
            });
        }

        if (!Schema::hasTable('payables')) {
            Schema::create('payables', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('status_id');
                $table->unsignedTinyInteger('category_id');
                $table->string('supplier_name', 255);
                $table->string('supplier_document', 20)->nullable()->comment('CPF/CNPJ');
                $table->string('description', 255);
                $table->decimal('amount', 12, 2);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('interest_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->nullable();
                $table->date('issue_date');
                $table->date('due_date');
                $table->date('competence_date')->nullable();
                $table->datetime('paid_at')->nullable();
                $table->unsignedBigInteger('bank_account_id')->nullable();
                $table->string('payment_method', 32)->nullable();
                $table->string('document_number', 64)->nullable()->comment('Nº nota/documento');
                $table->string('barcode', 128)->nullable()->comment('Código de barras boleto');
                $table->text('notes')->nullable();
                $table->string('reference_type', 64)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('created_by', 128)->nullable();
                $table->string('paid_by', 128)->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('status_id')
                    ->references('id')
                    ->on('domain_payable_status');

                $table->foreign('category_id')
                    ->references('id')
                    ->on('domain_financial_category');

                $table->index(['status_id', 'due_date']);
                $table->index(['competence_date', 'category_id']);
            });
        }

        if (!Schema::hasTable('provisions')) {
            Schema::create('provisions', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->comment('Formato: YYYY-MM');
            $table->string('type', 32)->comment('pcld, other');
            
            // Valores
            $table->decimal('calculated_amount', 12, 2)->comment('Valor calculado pelo sistema');
            $table->decimal('adjusted_amount', 12, 2)->nullable()->comment('Valor ajustado manualmente');
            $table->decimal('used_amount', 12, 2)->default(0)->comment('Valor utilizado/baixado');
            
            // Base de cálculo
            $table->json('calculation_base')->nullable()->comment('Dados usados no cálculo');
            
            // Controle
            $table->text('notes')->nullable();
            $table->string('created_by', 128)->nullable();
            $table->string('adjusted_by', 128)->nullable();
            $table->datetime('adjusted_at')->nullable();
            
            $table->timestamps();

            $table->unique(['period', 'type']);
        });
        }

        if (!Schema::hasTable('approvals')) {
            Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('status_id', false, true);
            
            // Operação que requer aprovação
            $table->string('operation_type', 64)->comment('discount, refund, payout, payable');
            $table->bigInteger('operation_id', false, true);
            
            // Valores
            $table->decimal('amount', 12, 2);
            $table->decimal('threshold_amount', 12, 2)->comment('Limite que disparou aprovação');
            
            // Solicitação
            $table->string('requested_by', 128);
            $table->text('request_reason')->nullable();
            $table->datetime('requested_at');
            
            // Decisão
            $table->string('decided_by', 128)->nullable();
            $table->text('decision_reason')->nullable();
            $table->datetime('decided_at')->nullable();
            
            // Expiração
            $table->datetime('expires_at')->nullable();
            
            $table->timestamps();

            $table->foreign('status_id')
                ->references('id')
                ->on('domain_approval_status');

            // Índices
            $table->index(['status_id', 'operation_type']);
            $table->index(['requested_by', 'status_id']);
        });
        }

        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'cost_center')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('cost_center', 64)->nullable()->after('external_reference')
                    ->comment('Centro de custo para análise gerencial');
                $table->unsignedTinyInteger('approval_status_id')->nullable()->after('cost_center');
                $table->unsignedBigInteger('approval_id')->nullable()->after('approval_status_id');

                $table->foreign('approval_status_id')
                    ->references('id')
                    ->on('domain_approval_status');

                $table->foreign('approval_id')
                    ->references('id')
                    ->on('approvals');
            });
        }

        if (Schema::hasTable('payouts') && !Schema::hasColumn('payouts', 'approval_status_id')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->unsignedTinyInteger('approval_status_id')->nullable()->after('total_amount');
                $table->unsignedBigInteger('approval_id')->nullable()->after('approval_status_id');

                $table->foreign('approval_status_id')
                    ->references('id')
                    ->on('domain_approval_status');

                $table->foreign('approval_id')
                    ->references('id')
                    ->on('approvals');
            });
        }

        $this->seedApprovalSettings();
    }

    /**
     * Insere apenas colunas que existem no schema atual.
     */
    protected function insertExisting(string $table, array $row): int
    {
        $allowed = array_flip(Schema::getColumnListing($table));
        $filtered = array_intersect_key($row, $allowed);
        if ($filtered === []) {
            throw new RuntimeException("Nenhuma coluna válida para inserir em {$table}");
        }

        return (int) DB::table($table)->insertGetId($filtered);
    }

    /**
     * Reverte as alterações.
     */
    public function down(): void
    {
        if (Schema::hasTable('payouts') && Schema::hasColumn('payouts', 'approval_status_id')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->dropForeign(['approval_status_id']);
                $table->dropForeign(['approval_id']);
                $table->dropColumn(['approval_status_id', 'approval_id']);
            });
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'cost_center')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign(['approval_status_id']);
                $table->dropForeign(['approval_id']);
                $table->dropColumn(['cost_center', 'approval_status_id', 'approval_id']);
            });
        }

        Schema::dropIfExists('approvals');
        Schema::dropIfExists('provisions');
        Schema::dropIfExists('payables');
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('domain_payable_status');
        Schema::dropIfExists('domain_approval_status');
        Schema::dropIfExists('domain_financial_category');
        Schema::dropIfExists('domain_transaction_type');
    }

    /**
     * Seed das configurações de aprovação.
     */
    protected function seedApprovalSettings(): void
    {
        if (!Schema::hasTable('setting_categories') || !Schema::hasTable('settings')) {
            return;
        }

        $existing = DB::table('setting_categories')->where('code', 'approval')->first();
        if ($existing) {
            $approvalCategoryId = (int) $existing->id;
        } else {
            $approvalCategoryId = $this->insertExisting('setting_categories', [
                'code' => 'approval',
                'name' => 'Aprovações',
                'description' => 'Configurações de limites e workflow de aprovação',
                'display_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $keyColumn = Schema::hasColumn('settings', 'key')
            ? 'key'
            : (Schema::hasColumn('settings', 'setting_key') ? 'setting_key' : null);
        if ($keyColumn === null) {
            return;
        }

        $settings = [
            [
                'key' => 'approval_discount_threshold',
                'name' => 'Limite Desconto s/ Aprovação',
                'description' => 'Percentual máximo de desconto sem necessidade de aprovação',
                'value' => '10',
                'default_value' => '10',
                'value_type' => 'decimal',
                'unit' => '%',
                'validation_rules' => json_encode(['min' => 0, 'max' => 30]),
                'is_editable' => true,
                'is_public' => false,
                'display_order' => 1,
            ],
            [
                'key' => 'approval_refund_threshold',
                'name' => 'Limite Reembolso s/ Aprovação',
                'description' => 'Valor máximo de reembolso sem necessidade de aprovação',
                'value' => '500',
                'default_value' => '500',
                'value_type' => 'decimal',
                'unit' => 'R$',
                'validation_rules' => json_encode(['min' => 0, 'max' => 5000]),
                'is_editable' => true,
                'is_public' => false,
                'display_order' => 2,
            ],
            [
                'key' => 'approval_payout_threshold',
                'name' => 'Limite Repasse s/ Aprovação',
                'description' => 'Valor máximo de repasse individual sem necessidade de aprovação',
                'value' => '5000',
                'default_value' => '5000',
                'value_type' => 'decimal',
                'unit' => 'R$',
                'validation_rules' => json_encode(['min' => 0, 'max' => 50000]),
                'is_editable' => true,
                'is_public' => false,
                'display_order' => 3,
            ],
            [
                'key' => 'approval_payable_threshold',
                'name' => 'Limite Pagamento s/ Aprovação',
                'description' => 'Valor máximo de conta a pagar sem necessidade de aprovação',
                'value' => '1000',
                'default_value' => '1000',
                'value_type' => 'decimal',
                'unit' => 'R$',
                'validation_rules' => json_encode(['min' => 0, 'max' => 10000]),
                'is_editable' => true,
                'is_public' => false,
                'display_order' => 4,
            ],
            [
                'key' => 'approval_expiration_hours',
                'name' => 'Expiração de Aprovação',
                'description' => 'Horas até uma solicitação de aprovação expirar',
                'value' => '48',
                'default_value' => '48',
                'value_type' => 'integer',
                'unit' => 'horas',
                'validation_rules' => json_encode(['min' => 1, 'max' => 168]),
                'is_editable' => true,
                'is_public' => false,
                'display_order' => 5,
            ],
        ];

        foreach ($settings as $setting) {
            $keyValue = $setting['key'];
            unset($setting['key']);
            $already = DB::table('settings')
                ->where('category_id', $approvalCategoryId)
                ->where($keyColumn, $keyValue)
                ->exists();
            if ($already) {
                continue;
            }

            $this->insertExisting('settings', array_merge($setting, [
                $keyColumn => $keyValue,
                'category_id' => $approvalCategoryId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
