<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyTaskAssignee implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'crm-notifications';

    public function handle(TaskCreated $event): void
    {
        $task = $event->task;
        $task->load(['lead', 'assignee']);

        if (!$task->assigned_to || !$task->assignee) {
            return;
        }

        // Aqui poderia enviar notificação por e-mail, push, etc.
        Log::channel('audit')->info('Task assigned notification', [
            'task_id' => $task->id,
            'assignee_id' => $task->assigned_to,
            'assignee_email' => $task->assignee->email,
            'due_at' => $task->due_at?->toIso8601String(),
        ]);
    }
}
