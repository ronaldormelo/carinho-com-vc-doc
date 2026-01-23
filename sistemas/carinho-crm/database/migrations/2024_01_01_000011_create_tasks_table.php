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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->datetime('due_at')->nullable();
            $table->unsignedTinyInteger('status_id');
            $table->text('notes')->nullable();

            // Foreign keys
            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('cascade');

            $table->foreign('assigned_to')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('status_id')
                ->references('id')
                ->on('domain_task_status')
                ->onDelete('restrict');

            // Indexes
            $table->index(['assigned_to', 'status_id', 'due_at'], 'idx_tasks_assignee');
            $table->index(['lead_id', 'status_id'], 'idx_tasks_lead');
            $table->index(['due_at', 'status_id'], 'idx_tasks_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
