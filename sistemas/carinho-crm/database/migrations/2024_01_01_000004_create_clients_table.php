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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->string('primary_contact', 255);
            $table->text('phone'); // Criptografado
            $table->text('address')->nullable(); // Criptografado
            $table->string('city', 128);
            $table->json('preferences_json')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('cascade');

            // Indexes
            $table->index(['city'], 'idx_clients_city');
            $table->unique(['lead_id'], 'uk_clients_lead');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
