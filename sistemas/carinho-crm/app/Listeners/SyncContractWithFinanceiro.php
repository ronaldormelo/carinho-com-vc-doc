<?php

namespace App\Listeners;

use App\Events\ContractSigned;
use App\Services\Integrations\CarinhoFinanceiroService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncContractWithFinanceiro implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'crm-integrations';

    public function __construct(
        protected CarinhoFinanceiroService $financeiroService
    ) {}

    public function handle(ContractSigned $event): void
    {
        $contract = $event->contract;
        $contract->load(['client', 'proposal.serviceType']);

        $this->financeiroService->updateContract($contract->id, [
            'status' => 'signed',
            'signed_at' => $contract->signed_at?->toIso8601String(),
            'start_date' => $contract->start_date?->toDateString(),
            'end_date' => $contract->end_date?->toDateString(),
        ]);
    }
}
