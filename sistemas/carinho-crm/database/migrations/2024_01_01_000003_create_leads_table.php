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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('phone'); // Criptografado
            $table->text('email')->nullable(); // Criptografado
            $table->string('city', 128);
            $table->unsignedTinyInteger('urgency_id');
            $table->unsignedTinyInteger('service_type_id');
            $table->string('source', 128);
            $table->unsignedTinyInteger('status_id');
            $table->unsignedBigInteger('utm_id')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('urgency_id')
                ->references('id')
                ->on('domain_urgency_level')
                ->onDelete('restrict');
            
            $table->foreign('service_type_id')
                ->references('id')
                ->on('domain_service_type')
                ->onDelete('restrict');
            
            $table->foreign('status_id')
                ->references('id')
                ->on('domain_lead_status')
                ->onDelete('restrict');

            // Indexes para performance
            $table->index(['status_id', 'city'], 'idx_leads_status_city');
            $table->index(['created_at'], 'idx_leads_created');
            $table->index(['source'], 'idx_leads_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
