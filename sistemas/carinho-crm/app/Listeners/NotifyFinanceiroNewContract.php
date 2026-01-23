<?php

namespace App\Listeners;

use App\Events\DealWon;
use App\Services\Integrations\CarinhoFinanceiroService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyFinanceiroNewContract implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'crm-integrations';
    public int $delay = 60; // Aguarda criação do contrato

    public function __construct(
        protected CarinhoFinanceiroService $financeiroService
    ) {}

    public function handle(DealWon $event): void
    {
        $deal = $event->deal;
        $deal->load(['lead.client.contracts.proposal.serviceType']);

        $client = $deal->lead?->client;
        if (!$client) {
            return;
        }

        $contract = $client->getActiveContract();
        if (!$contract) {
            return;
        }

        $this->financeiroService->registerContract($contract->id, [
            'client_id' => $client->id,
            'client_name' => $client->display_name,
            'service_type' => $contract->proposal?->serviceType?->code,
            'monthly_value' => $contract->proposal?->price ?? 0,
            'start_date' => $contract->start_date?->toDateString(),
            'end_date' => $contract->end_date?->toDateString(),
        ]);
    }
}
