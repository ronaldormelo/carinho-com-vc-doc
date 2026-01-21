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
        Schema::create('care_needs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedTinyInteger('patient_type_id');
            $table->json('conditions_json')->nullable();
            $table->text('notes')->nullable();

            // Foreign keys
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');

            $table->foreign('patient_type_id')
                ->references('id')
                ->on('domain_patient_type')
                ->onDelete('restrict');

            // Index
            $table->index(['patient_type_id'], 'idx_care_needs_patient_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_needs');
    }
};
