<?php

namespace App\Listeners;

use App\Events\ContractSigned;
use Illuminate\Support\Facades\Log;

class LogContractActivity
{
    public function handle(ContractSigned $event): void
    {
        $contract = $event->contract;

        Log::channel('audit')->info('Contract Signed', [
            'contract_id' => $contract->id,
            'client_id' => $contract->client_id,
            'signed_at' => $contract->signed_at?->toIso8601String(),
        ]);
    }
}
