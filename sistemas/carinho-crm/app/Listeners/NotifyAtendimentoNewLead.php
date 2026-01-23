<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Services\Integrations\CarinhoAtendimentoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyAtendimentoNewLead implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'crm-integrations';

    public function __construct(
        protected CarinhoAtendimentoService $atendimentoService
    ) {}

    public function handle(LeadCreated $event): void
    {
        $lead = $event->lead;
        $lead->load(['urgency', 'serviceType']);

        $this->atendimentoService->notifyNewLead($lead->id, [
            'name' => $lead->name,
            'phone' => $lead->phone,
            'urgency' => $lead->urgency?->code ?? 'normal',
            'service_type' => $lead->serviceType?->code ?? null,
            'source' => $lead->source,
        ]);
    }
}
