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
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedTinyInteger('channel_id');
            $table->text('summary');
            $table->datetime('occurred_at');

            // Foreign keys
            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('cascade');

            $table->foreign('channel_id')
                ->references('id')
                ->on('domain_interaction_channel')
                ->onDelete('restrict');

            // Indexes
            $table->index(['lead_id', 'occurred_at'], 'idx_interactions_lead_time');
            $table->index(['channel_id', 'occurred_at'], 'idx_interactions_channel_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
