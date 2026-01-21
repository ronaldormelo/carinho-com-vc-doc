<?php

namespace App\Listeners;

use App\Events\LeadStatusChanged;
use App\Services\Integrations\CarinhoAtendimentoService;
use App\Models\Domain\DomainLeadStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncLeadStatusWithAtendimento implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'crm-integrations';

    public function __construct(
        protected CarinhoAtendimentoService $atendimentoService
    ) {}

    public function handle(LeadStatusChanged $event): void
    {
        $lead = $event->lead;
        $lead->load('status');

        $statusCode = $lead->status?->code ?? 'unknown';

        $this->atendimentoService->updateLeadStatus(
            $lead->id,
            $statusCode,
            "Status alterado de {$event->previousStatusId} para {$lead->status_id}"
        );
    }
}
