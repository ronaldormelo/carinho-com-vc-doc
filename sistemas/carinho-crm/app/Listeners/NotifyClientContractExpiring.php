<?php

namespace App\Listeners;

use App\Events\ContractExpiring;
use App\Services\Integrations\ZApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyClientContractExpiring implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'crm-notifications';

    public function __construct(
        protected ZApiService $zApiService
    ) {}

    public function handle(ContractExpiring $event): void
    {
        if (!$this->zApiService->isEnabled()) {
            return;
        }

        $contract = $event->contract;
        $contract->load('client');

        if (!$contract->client?->phone) {
            return;
        }

        $this->zApiService->sendContractExpiringNotification(
            $contract->client->phone,
            $contract->client->display_name,
            $event->daysRemaining
        );
    }

    public function shouldQueue(ContractExpiring $event): bool
    {
        // Só notifica se faltar 30, 15 ou 7 dias
        return in_array($event->daysRemaining, [30, 15, 7]);
    }
}
