<?php

namespace App\Listeners;

use App\Events\ContractSigned;
use App\Models\Domain\DomainLeadStatus;
use Illuminate\Contracts\Queue\ShouldQueue;

class ActivateClient implements ShouldQueue
{
    public string $queue = 'crm-default';

    public function handle(ContractSigned $event): void
    {
        $contract = $event->contract;
        $contract->load('client.lead');

        // Atualiza lead para ativo se ainda não estiver
        if ($contract->client?->lead && $contract->client->lead->status_id !== DomainLeadStatus::ACTIVE) {
            $contract->client->lead->update([
                'status_id' => DomainLeadStatus::ACTIVE,
            ]);
        }
    }
}
