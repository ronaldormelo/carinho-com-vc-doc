<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabelas canônicas de cobrança que estavam só em database/schema.sql.
     * Idempotente: não recria o que já existir.
     */
    public function up(): void
    {
        $this->createDomain('domain_payment_method', [
            [1, 'pix', 'Pix'],
            [2, 'boleto', 'Boleto'],
            [3, 'card', 'Card'],
        ]);
        $this->createDomain('domain_account_status', [
            [1, 'active', 'Active'],
            [2, 'inactive', 'Inactive'],
        ]);
        $this->createDomain('domain_invoice_status', [
            [1, 'open', 'Open'],
            [2, 'paid', 'Paid'],
            [3, 'overdue', 'Overdue'],
            [4, 'canceled', 'Canceled'],
        ]);
        $this->createDomain('domain_payment_status', [
            [1, 'pending', 'Pending'],
            [2, 'paid', 'Paid'],
            [3, 'failed', 'Failed'],
            [4, 'refunded', 'Refunded'],
        ]);
        $this->createDomain('domain_payout_status', [
            [1, 'open', 'Open'],
            [2, 'paid', 'Paid'],
            [3, 'canceled', 'Canceled'],
        ]);
        $this->createDomain('domain_service_type', [
            [1, 'horista', 'Horista'],
            [2, 'diario', 'Diario'],
            [3, 'mensal', 'Mensal'],
        ]);
        $this->createDomain('domain_owner_type', [
            [1, 'client', 'Client'],
            [2, 'caregiver', 'Caregiver'],
            [3, 'company', 'Company'],
        ]);
        $this->createDomain('domain_reconciliation_status', [
            [1, 'open', 'Open'],
            [2, 'closed', 'Closed'],
        ]);

        if (!Schema::hasTable('billing_accounts')) {
            Schema::create('billing_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedTinyInteger('payment_method_id');
                $table->unsignedTinyInteger('status_id');
                $table->timestamps();
                $table->foreign('payment_method_id')->references('id')->on('domain_payment_method');
                $table->foreign('status_id')->references('id')->on('domain_account_status');
            });
        }

        if (!Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('owner_type_id');
                $table->unsignedBigInteger('owner_id');
                $table->string('bank_name', 128);
                $table->string('account_hash', 255);
                $table->timestamps();
                $table->foreign('owner_type_id')->references('id')->on('domain_owner_type');
            });
        }

        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('contract_id');
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->date('due_date')->nullable();
                $table->unsignedTinyInteger('status_id');
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('cancellation_fee', 12, 2)->default(0);
                $table->string('notes', 2000)->nullable();
                $table->string('external_reference', 128)->nullable();
                $table->string('cost_center', 64)->nullable();
                $table->unsignedTinyInteger('approval_status_id')->nullable();
                $table->unsignedBigInteger('approval_id')->nullable();
                $table->timestamps();
                $table->foreign('status_id')->references('id')->on('domain_invoice_status');
            });
        }

        if (!Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('invoice_id');
                $table->date('service_date');
                $table->string('description', 255);
                $table->decimal('qty', 12, 2)->default(0);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('amount', 12, 2)->default(0);
                $table->foreign('invoice_id')->references('id')->on('invoices');
            });
        }

        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedTinyInteger('method_id');
                $table->decimal('amount', 12, 2)->default(0);
                $table->unsignedTinyInteger('status_id');
                $table->dateTime('paid_at')->nullable();
                $table->string('external_id', 128)->nullable();
                $table->foreign('invoice_id')->references('id')->on('invoices');
                $table->foreign('method_id')->references('id')->on('domain_payment_method');
                $table->foreign('status_id')->references('id')->on('domain_payment_status');
            });
        }

        if (!Schema::hasTable('payouts')) {
            Schema::create('payouts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('caregiver_id');
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->unsignedTinyInteger('status_id');
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->unsignedTinyInteger('approval_status_id')->nullable();
                $table->unsignedBigInteger('approval_id')->nullable();
                $table->timestamps();
                $table->foreign('status_id')->references('id')->on('domain_payout_status');
            });
        }

        if (!Schema::hasTable('payout_items')) {
            Schema::create('payout_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payout_id');
                $table->unsignedBigInteger('service_id');
                $table->decimal('amount', 12, 2)->default(0);
                $table->decimal('commission_percent', 6, 2)->default(0);
                $table->foreign('payout_id')->references('id')->on('payouts');
            });
        }

        if (!Schema::hasTable('price_plans')) {
            Schema::create('price_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name', 128);
                $table->unsignedTinyInteger('service_type_id');
                $table->decimal('base_price', 12, 2)->default(0);
                $table->boolean('active')->default(true);
                $table->foreign('service_type_id')->references('id')->on('domain_service_type');
            });
        }

        if (!Schema::hasTable('fiscal_documents')) {
            Schema::create('fiscal_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('invoice_id');
                $table->string('doc_number', 64);
                $table->dateTime('issued_at');
                $table->string('file_url', 512);
                $table->string('status', 32)->nullable();
                $table->foreign('invoice_id')->references('id')->on('invoices');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_documents');
        Schema::dropIfExists('price_plans');
        Schema::dropIfExists('payout_items');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('billing_accounts');
    }

    private function createDomain(string $table, array $rows): void
    {
        if (!Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $blueprint) {
                $blueprint->unsignedTinyInteger('id')->primary();
                $blueprint->string('code', 32)->unique();
                $blueprint->string('label', 64);
            });
        }

        foreach ($rows as [$id, $code, $label]) {
            $exists = DB::table($table)->where('id', $id)->exists();
            if (!$exists) {
                DB::table($table)->insert(['id' => $id, 'code' => $code, 'label' => $label]);
            }
        }
    }
};
