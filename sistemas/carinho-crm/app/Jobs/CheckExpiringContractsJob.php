<?php

namespace App\Jobs;

use App\Models\Contract;
use App\Events\ContractExpiring;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckExpiringContractsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'crm-default';

    public function handle(): void
    {
        Log::info('Verificando contratos próximos do vencimento');

        // Contratos que expiram em 30 dias
        $expiringIn30 = Contract::vigente()
            ->whereDate('end_date', now()->addDays(30)->toDateString())
            ->get();

        foreach ($expiringIn30 as $contract) {
            event(new ContractExpiring($contract, 30));
        }

        // Contratos que expiram em 15 dias
        $expiringIn15 = Contract::vigente()
            ->whereDate('end_date', now()->addDays(15)->toDateString())
            ->get();

        foreach ($expiringIn15 as $contract) {
            event(new ContractExpiring($contract, 15));
        }

        // Contratos que expiram em 7 dias
        $expiringIn7 = Contract::vigente()
            ->whereDate('end_date', now()->addDays(7)->toDateString())
            ->get();

        foreach ($expiringIn7 as $contract) {
            event(new ContractExpiring($contract, 7));
        }

        Log::info('Verificação de contratos concluída', [
            'expiring_30' => $expiringIn30->count(),
            'expiring_15' => $expiringIn15->count(),
            'expiring_7' => $expiringIn7->count(),
        ]);
    }
}
