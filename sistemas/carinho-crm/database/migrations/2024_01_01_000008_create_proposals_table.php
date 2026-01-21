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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->unsignedTinyInteger('service_type_id');
            $table->decimal('price', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->datetime('expires_at')->nullable();

            // Foreign keys
            $table->foreign('deal_id')
                ->references('id')
                ->on('deals')
                ->onDelete('cascade');

            $table->foreign('service_type_id')
                ->references('id')
                ->on('domain_service_type')
                ->onDelete('restrict');

            // Indexes
            $table->index(['deal_id'], 'idx_proposals_deal');
            $table->index(['expires_at'], 'idx_proposals_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
