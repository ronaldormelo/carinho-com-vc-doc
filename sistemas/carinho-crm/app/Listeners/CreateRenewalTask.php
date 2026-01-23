<?php

namespace App\Listeners;

use App\Events\ContractExpiring;
use App\Services\TaskService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateRenewalTask implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'crm-default';

    public function __construct(
        protected TaskService $taskService
    ) {}

    public function handle(ContractExpiring $event): void
    {
        $contract = $event->contract;
        $contract->load('client.lead');

        if (!$contract->client?->lead_id) {
            return;
        }

        // Cria tarefa de renovação
        $this->taskService->createTask([
            'lead_id' => $contract->client->lead_id,
            'due_at' => now()->addDays(max(1, $event->daysRemaining - 7)),
            'notes' => "Contrato #{$contract->id} expira em {$event->daysRemaining} dias. Verificar interesse em renovação.",
        ]);
    }
}
