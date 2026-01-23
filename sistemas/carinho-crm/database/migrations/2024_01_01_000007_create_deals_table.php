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
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('stage_id');
            $table->decimal('value_estimated', 12, 2)->default(0);
            $table->unsignedTinyInteger('status_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('cascade');

            $table->foreign('stage_id')
                ->references('id')
                ->on('pipeline_stages')
                ->onDelete('restrict');

            $table->foreign('status_id')
                ->references('id')
                ->on('domain_deal_status')
                ->onDelete('restrict');

            // Indexes
            $table->index(['stage_id', 'status_id'], 'idx_deals_stage_status');
            $table->index(['lead_id'], 'idx_deals_lead');
            $table->index(['created_at'], 'idx_deals_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
