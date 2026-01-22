<?php

namespace App\Jobs;

use App\Services\SyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para sincronizacao entre sistemas.
 *
 * Executado periodicamente para manter dados
 * consistentes entre os sistemas do ecossistema.
 */
class SyncSystems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600; // 10 minutos

    /**
     * Tipo de sincronizacao.
     */
    private string $type;

    /**
     * Cria nova instancia do job.
     */
    public function __construct(string $type = 'full')
    {
        $this->type = $type;
        $this->onQueue('integrations-low');
    }

    /**
     * Executa o job.
     */
    public function handle(SyncService $syncService): void
    {
        Log::info('Starting system sync', [
            'type' => $this->type,
        ]);

        $results = match ($this->type) {
            'crm_operacao' => [$syncService->syncCrmToOperacao()],
            'operacao_financeiro' => [$syncService->syncOperacaoToFinanceiro()],
            'crm_financeiro' => [$syncService->syncCrmToFinanceiro()],
            'cuidadores_crm' => [$syncService->syncCuidadoresToCrm()],
            'full' => $syncService->fullSync(),
            default => throw new \InvalidArgumentException("Unknown sync type: {$this->type}"),
        };

        $statuses = [];
        foreach ($results as $key => $job) {
            $statuses[$key] = [
                'job_id' => $job->id,
                'status' => $job->isDone() ? 'done' : ($job->isFailed() ? 'failed' : 'unknown'),
                'duration' => $job->getDurationInSeconds(),
            ];
        }

        Log::info('System sync completed', [
            'type' => $this->type,
            'results' => $statuses,
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('System sync failed', [
            'type' => $this->type,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Tags para monitoramento.
     */
    public function tags(): array
    {
        return ['sync', 'type:' . $this->type];
    }
}
