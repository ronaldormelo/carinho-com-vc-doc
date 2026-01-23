<?php

namespace App\Jobs;

use App\Services\EventProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para processar fila de retry.
 *
 * Executado periodicamente para reprocessar eventos
 * que falharam e estao aguardando retry.
 */
class ProcessRetryQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    /**
     * Limite de eventos a processar.
     */
    private int $limit;

    /**
     * Cria nova instancia do job.
     */
    public function __construct(int $limit = 100)
    {
        $this->limit = $limit;
        $this->onQueue('integrations-retry');
    }

    /**
     * Executa o job.
     */
    public function handle(EventProcessor $processor): void
    {
        Log::info('Processing retry queue', [
            'limit' => $this->limit,
        ]);

        $processed = $processor->processRetryQueue($this->limit);

        Log::info('Retry queue processing completed', [
            'processed' => $processed,
        ]);
    }

    /**
     * Tags para monitoramento.
     */
    public function tags(): array
    {
        return ['retry_queue'];
    }
}
