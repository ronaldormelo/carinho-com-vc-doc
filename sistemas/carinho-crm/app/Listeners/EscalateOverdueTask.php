<?php

namespace App\Listeners;

use App\Events\TaskOverdue;
use App\Services\Integrations\CarinhoAtendimentoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class EscalateOverdueTask implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'crm-notifications';

    public function __construct(
        protected CarinhoAtendimentoService $atendimentoService
    ) {}

    public function handle(TaskOverdue $event): void
    {
        $task = $event->task;
        $task->load(['lead', 'assignee']);

        Log::channel('audit')->warning('Task overdue escalation', [
            'task_id' => $task->id,
            'lead_id' => $task->lead_id,
            'assignee_id' => $task->assigned_to,
            'due_at' => $task->due_at?->toIso8601String(),
        ]);

        // Envia alerta para atendimento
        $this->atendimentoService->sendAlert('task_overdue', [
            'priority' => 'high',
            'lead_id' => $task->lead_id,
            'message' => "Tarefa #{$task->id} está atrasada. Lead: {$task->lead?->name}",
        ]);
    }
}
