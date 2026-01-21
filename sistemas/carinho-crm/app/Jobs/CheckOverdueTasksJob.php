<?php

namespace App\Jobs;

use App\Models\Task;
use App\Events\TaskOverdue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckOverdueTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'crm-default';

    public function handle(): void
    {
        Log::info('Verificando tarefas atrasadas');

        $overdueTasks = Task::overdue()
            ->with(['lead', 'assignee'])
            ->get();

        foreach ($overdueTasks as $task) {
            event(new TaskOverdue($task));
        }

        Log::info('Verificação de tarefas concluída', [
            'overdue_count' => $overdueTasks->count(),
        ]);
    }
}
