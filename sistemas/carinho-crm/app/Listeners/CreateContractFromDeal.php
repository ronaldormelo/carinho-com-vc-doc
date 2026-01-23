<?php

namespace App\Listeners;

use App\Events\DealWon;
use App\Services\ContractService;
use App\Services\LeadService;
use App\Models\Domain\DomainLeadStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateContractFromDeal implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'crm-default';

    public function __construct(
        protected ContractService $contractService,
        protected LeadService $leadService
    ) {}

    public function handle(DealWon $event): void
    {
        $deal = $event->deal;
        $deal->load(['lead.client', 'proposals']);

        // Atualiza status do lead para ativo
        if ($deal->lead && $deal->lead->status_id !== DomainLeadStatus::ACTIVE) {
            $this->leadService->updateLead($deal->lead, [
                'status_id' => DomainLeadStatus::ACTIVE,
            ]);
        }

        // Se não há cliente, não pode criar contrato
        $client = $deal->lead?->client;
        if (!$client) {
            return;
        }

        // Pega a última proposta do deal
        $proposal = $deal->getLatestProposal();
        if (!$proposal) {
            return;
        }

        // Cria contrato
        $this->contractService->createContract([
            'client_id' => $client->id,
            'proposal_id' => $proposal->id,
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);
    }
}
