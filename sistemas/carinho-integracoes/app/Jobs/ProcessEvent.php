<?php

namespace App\Jobs;

use App\Models\IntegrationEvent;
use App\Services\EventProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para processamento de eventos de integracao.
 *
 * Executado pelo worker de filas, processa eventos
 * de forma assincrona aplicando mapeamentos e
 * despachando para sistemas alvo.
 */
class ProcessEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero de tentativas antes de falhar.
     */
    public int $tries = 3;

    /**
     * Tempo maximo de execucao em segundos.
     */
    public int $timeout = 60;

    /**
     * Backoff em segundos entre tentativas.
     */
    public array $backoff = [30, 60, 120];

    /**
     * Cria nova instancia do job.
     */
    public function __construct(
        public IntegrationEvent $event
    ) {
        $this->onQueue('integrations');
    }

    /**
     * Executa o job.
     */
    public function handle(EventProcessor $processor): void
    {
        Log::info('Processing event', [
            'event_id' => $this->event->id,
            'event_type' => $this->event->event_type,
        ]);

        $processor->execute($this->event);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Event processing failed permanently', [
            'event_id' => $this->event->id,
            'event_type' => $this->event->event_type,
            'error' => $exception->getMessage(),
        ]);

        $this->event->markAsFailed();
    }

    /**
     * Tags para monitoramento.
     */
    public function tags(): array
    {
        return [
            'event:' . $this->event->event_type,
            'source:' . $this->event->source_system,
            'event_id:' . $this->event->id,
        ];
    }
}
