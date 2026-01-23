<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use App\Events\TaskCreated;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $taskService
    ) {}

    /**
     * Lista todas as tarefas com filtros e paginação
     */
    public function index(Request $request)
    {
        $query = Task::with(['lead', 'status', 'assignee']);

        // Filtros
        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->has('lead_id')) {
            $query->forLead($request->lead_id);
        }

        if ($request->has('assigned_to')) {
            $query->assignedTo($request->assigned_to);
        }

        if ($request->has('unassigned') && $request->unassigned) {
            $query->unassigned();
        }

        if ($request->has('overdue') && $request->overdue) {
            $query->overdue();
        }

        if ($request->has('due_today') && $request->due_today) {
            $query->dueToday();
        }

        if ($request->has('due_soon')) {
            $query->dueSoon((int) $request->due_soon);
        }

        if ($request->has('open_only') && $request->open_only) {
            $query->open();
        }

        // Ordenação
        $sortField = $request->get('sort_by', 'due_at');
        $sortDirection = $request->get('sort_dir', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Paginação
        $perPage = min($request->get('per_page', 15), 100);
        $tasks = $query->paginate($perPage);

        return TaskResource::collection($tasks);
    }

    /**
     * Cria uma nova tarefa
     */
    public function store(TaskRequest $request)
    {
        $task = $this->taskService->createTask($request->validated());

        event(new TaskCreated($task));

        return $this->createdResponse(
            new TaskResource($task->load(['lead', 'status', 'assignee'])),
            'Tarefa criada com sucesso'
        );
    }

    /**
     * Exibe uma tarefa específica
     */
    public function show(Task $task)
    {
        $task->load([
            'lead.urgency',
            'lead.serviceType',
            'lead.status',
            'status',
            'assignee',
        ]);

        return new TaskResource($task);
    }

    /**
     * Atualiza uma tarefa
     */
    public function update(TaskRequest $request, Task $task)
    {
        $task = $this->taskService->updateTask($task, $request->validated());

        return $this->successResponse(
            new TaskResource($task->load(['lead', 'status', 'assignee'])),
            'Tarefa atualizada com sucesso'
        );
    }

    /**
     * Remove uma tarefa
     */
    public function destroy(Task $task)
    {
        $task->delete();

        return $this->successResponse(null, 'Tarefa excluída com sucesso');
    }

    /**
     * Marca tarefa como concluída
     */
    public function complete(Task $task)
    {
        if (!$task->markAsDone()) {
            return $this->errorResponse('Não foi possível concluir a tarefa', 422);
        }

        return $this->successResponse(
            new TaskResource($task->load(['lead', 'status', 'assignee'])),
            'Tarefa concluída com sucesso'
        );
    }

    /**
     * Cancela uma tarefa
     */
    public function cancel(Task $task)
    {
        if (!$task->cancel()) {
            return $this->errorResponse('Não foi possível cancelar a tarefa', 422);
        }

        return $this->successResponse(
            new TaskResource($task->load(['lead', 'status', 'assignee'])),
            'Tarefa cancelada com sucesso'
        );
    }

    /**
     * Atribui tarefa a um usuário
     */
    public function assign(Request $request, Task $task)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $task->assignTo($request->user_id);

        return $this->successResponse(
            new TaskResource($task->load(['lead', 'status', 'assignee'])),
            'Tarefa atribuída com sucesso'
        );
    }

    /**
     * Remove atribuição da tarefa
     */
    public function unassign(Task $task)
    {
        $task->unassign();

        return $this->successResponse(
            new TaskResource($task->load(['lead', 'status', 'assignee'])),
            'Atribuição removida com sucesso'
        );
    }

    /**
     * Lista tarefas do usuário autenticado
     */
    public function myTasks(Request $request)
    {
        $query = Task::with(['lead', 'status'])
            ->assignedTo(auth()->id())
            ->open();

        if ($request->has('due_soon')) {
            $query->dueSoon((int) $request->due_soon);
        }

        $tasks = $query->orderBy('due_at', 'asc')->get();

        return TaskResource::collection($tasks);
    }

    /**
     * Lista tarefas atrasadas
     */
    public function overdue()
    {
        $tasks = Task::with(['lead', 'status', 'assignee'])
            ->overdue()
            ->orderBy('due_at', 'asc')
            ->get();

        return TaskResource::collection($tasks);
    }
}
