<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addForeign('cash_transactions', 'bank_account_id', 'bank_accounts');
        $this->addForeign('payables', 'bank_account_id', 'bank_accounts');
    }

    public function down(): void
    {
        $this->dropForeignIfExists('cash_transactions', 'cash_transactions_bank_account_id_foreign');
        $this->dropForeignIfExists('payables', 'payables_bank_account_id_foreign');
    }

    private function addForeign(string $table, string $column, string $referenced): void
    {
        if (!Schema::hasTable($table) || !Schema::hasTable($referenced) || !Schema::hasColumn($table, $column)) {
            return;
        }

        $name = $table . '_' . $column . '_foreign';
        if ($this->hasForeign($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referenced): void {
            $blueprint->foreign($column)->references('id')->on($referenced);
        });
    }

    private function hasForeign(string $table, string $name): bool
    {
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME AS n
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            [$table, $name, 'FOREIGN KEY']
        );

        return $row !== null;
    }

    private function dropForeignIfExists(string $table, string $name): void
    {
        if (!Schema::hasTable($table) || !$this->hasForeign($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name): void {
            $blueprint->dropForeign($name);
        });
    }
};
