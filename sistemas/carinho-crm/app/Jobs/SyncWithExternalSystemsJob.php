<?php

namespace App\Jobs;

use App\Services\Integrations\CarinhoOperacaoService;
use App\Services\Integrations\CarinhoFinanceiroService;
use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWithExternalSystemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'crm-integrations';
    public int $timeout = 120;

    public function handle(
        CarinhoOperacaoService $operacaoService,
        CarinhoFinanceiroService $financeiroService
    ): void {
        Log::info('Iniciando sincronização com sistemas externos');

        // Sincroniza contratos ativos com operação
        $activeContracts = Contract::active()
            ->with(['client', 'proposal.serviceType'])
            ->whereDate('updated_at', '>=', now()->subDay())
            ->get();

        foreach ($activeContracts as $contract) {
            try {
                $operacaoService->syncContract($contract->id, [
                    'client_id' => $contract->client_id,
                    'service_type' => $contract->proposal?->serviceType?->code,
                    'start_date' => $contract->start_date?->toDateString(),
                    'end_date' => $contract->end_date?->toDateString(),
                ]);

                $financeiroService->updateContract($contract->id, [
                    'status' => 'active',
                    'monthly_value' => $contract->proposal?->price,
                ]);
            } catch (\Exception $e) {
                Log::error("Erro ao sincronizar contrato #{$contract->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Sincronização concluída', [
            'contracts_synced' => $activeContracts->count(),
        ]);
    }
}
