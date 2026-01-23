<?php

namespace App\Listeners;

use App\Events\LeadConverted;
use App\Services\Integrations\CarinhoOperacaoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyOperacaoNewClient implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'crm-integrations';

    public function __construct(
        protected CarinhoOperacaoService $operacaoService
    ) {}

    public function handle(LeadConverted $event): void
    {
        $lead = $event->lead;
        $lead->load(['client.careNeeds', 'serviceType']);

        if (!$lead->client) {
            return;
        }

        $this->operacaoService->notifyNewClient($lead->client->id, [
            'name' => $lead->name,
            'phone' => $lead->client->phone,
            'city' => $lead->client->city,
            'address' => $lead->client->address,
            'care_needs' => $lead->client->careNeeds?->pluck('patient_type_id')->toArray() ?? [],
            'service_type' => $lead->serviceType?->code,
        ]);
    }
}
