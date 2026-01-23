<?php

namespace App\Listeners;

use App\Events\LeadConverted;
use App\Services\ClientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateClientFromLead implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'crm-default';

    public function __construct(
        protected ClientService $clientService
    ) {}

    public function handle(LeadConverted $event): void
    {
        $lead = $event->lead;

        // Verifica se já existe cliente para este lead
        if ($lead->client()->exists()) {
            return;
        }

        $this->clientService->createClient([
            'lead_id' => $lead->id,
            'primary_contact' => $lead->name,
            'phone' => $lead->phone,
            'city' => $lead->city,
        ]);
    }
}
