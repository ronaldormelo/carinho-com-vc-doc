<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Domain\DomainTaskStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskService
{
    /**
     * Cria uma nova tarefa
     */
    public function createTask(array $data): Task
    {
        return DB::transaction(function () use ($data) {
            // Status inicial é "open"
            $data['status_id'] = $data['status_id'] ?? DomainTaskStatus::OPEN;

            $task = Task::create($data);

            Log::channel('audit')->info('Tarefa criada', [
                'task_id' => $task->id,
                'lead_id' => $data['lead_id'],
                'assigned_to' => $data['assigned_to'] ?? null,
            ]);

            return $task;
        });
    }

    /**
     * Atualiza uma tarefa existente
     */
    public function updateTask(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            $task->update($data);

            Log::channel('audit')->info('Tarefa atualizada', [
                'task_id' => $task->id,
                'changes' => $task->getChanges(),
            ]);

            return $task->fresh();
        });
    }

    /**
     * Cria tarefa de follow-up para um lead
     */
    public function createFollowUpTask(int $leadId, int $daysFromNow = 1, ?int $assignedTo = null, ?string $notes = null): Task
    {
        return $this->createTask([
            'lead_id' => $leadId,
            'assigned_to' => $assignedTo,
            'due_at' => now()->addDays($daysFromNow),
            'notes' => $notes ?? 'Follow-up agendado automaticamente',
        ]);
    }

    /**
     * Cria tarefa de renovação de contrato
     */
    public function createRenewalTask(int $leadId, \DateTime $dueAt, ?int $assignedTo = null): Task
    {
        return $this->createTask([
            'lead_id' => $leadId,
            'assigned_to' => $assignedTo,
            'due_at' => $dueAt,
            'notes' => 'Verificar renovação de contrato',
        ]);
    }

    /**
     * Obtém tarefas atrasadas
     */
    public function getOverdueTasks(?int $userId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Task::with(['lead', 'status', 'assignee'])
            ->overdue();

        if ($userId) {
            $query->assignedTo($userId);
        }

        return $query->orderBy('due_at', 'asc')->get();
    }

    /**
     * Obtém tarefas para hoje
     */
    public function getTodayTasks(?int $userId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Task::with(['lead', 'status', 'assignee'])
            ->dueToday();

        if ($userId) {
            $query->assignedTo($userId);
        }

        return $query->orderBy('due_at', 'asc')->get();
    }

    /**
     * Obtém resumo de tarefas por usuário
     */
    public function getUserTasksSummary(int $userId): array
    {
        return [
            'open' => Task::assignedTo($userId)->open()->count(),
            'overdue' => Task::assignedTo($userId)->overdue()->count(),
            'due_today' => Task::assignedTo($userId)->dueToday()->count(),
            'due_soon' => Task::assignedTo($userId)->dueSoon(3)->count(),
            'completed_this_week' => Task::assignedTo($userId)
                ->done()
                ->whereBetween('updated_at', [now()->startOfWeek(), now()])
                ->count(),
        ];
    }

    /**
     * Obtém estatísticas gerais de tarefas
     */
    public function getStatistics(): array
    {
        return [
            'total_open' => Task::open()->count(),
            'total_overdue' => Task::overdue()->count(),
            'due_today' => Task::dueToday()->count(),
            'unassigned' => Task::open()->unassigned()->count(),
            'completed_today' => Task::done()
                ->whereDate('updated_at', today())
                ->count(),
            'completed_this_week' => Task::done()
                ->whereBetween('updated_at', [now()->startOfWeek(), now()])
                ->count(),
        ];
    }
}
