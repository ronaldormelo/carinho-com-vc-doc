<?php

namespace App\Jobs;

use App\Services\ContentCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para publicar conteudos agendados.
 */
class PublishScheduledContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero de tentativas.
     */
    public int $tries = 3;

    /**
     * Tempo limite em segundos.
     */
    public int $timeout = 120;

    /**
     * Tempo de backoff entre tentativas.
     */
    public array $backoff = [60, 120, 300];

    public function __construct(
        private ?int $contentId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ContentCalendarService $contentService): void
    {
        try {
            if ($this->contentId) {
                // Publica conteudo especifico
                Log::info('Publishing specific content', ['content_id' => $this->contentId]);
                $contentService->publish($this->contentId);
            } else {
                // Processa todos os conteudos pendentes
                Log::info('Processing pending publications');
                $results = $contentService->processPendingPublications();

                Log::info('Pending publications processed', [
                    'total' => count($results),
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Content publication failed', [
                'content_id' => $this->contentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PublishScheduledContent job failed permanently', [
            'content_id' => $this->contentId,
            'error' => $exception->getMessage(),
        ]);
    }
}
