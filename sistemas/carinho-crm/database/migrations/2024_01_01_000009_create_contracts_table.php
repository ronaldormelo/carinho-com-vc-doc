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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('proposal_id');
            $table->unsignedTinyInteger('status_id');
            $table->datetime('signed_at')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Foreign keys
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');

            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->onDelete('restrict');

            $table->foreign('status_id')
                ->references('id')
                ->on('domain_contract_status')
                ->onDelete('restrict');

            // Indexes
            $table->index(['client_id', 'status_id'], 'idx_contracts_client_status');
            $table->index(['end_date', 'status_id'], 'idx_contracts_expiring');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
