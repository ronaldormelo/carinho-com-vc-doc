<?php

namespace App\Jobs;

use App\Services\CampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para sincronizar metricas de campanhas.
 */
class SyncCampaignMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero de tentativas.
     */
    public int $tries = 3;

    /**
     * Tempo limite em segundos.
     */
    public int $timeout = 300;

    /**
     * Tempo de backoff entre tentativas.
     */
    public array $backoff = [30, 60, 120];

    public function __construct(
        private int $campaignId,
        private string $startDate,
        private string $endDate
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CampaignService $campaignService): void
    {
        try {
            Log::info('Syncing campaign metrics', [
                'campaign_id' => $this->campaignId,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
            ]);

            $campaignService->syncMetrics($this->campaignId, $this->startDate, $this->endDate);

            Log::info('Campaign metrics synced successfully', [
                'campaign_id' => $this->campaignId,
            ]);

        } catch (\Throwable $e) {
            Log::error('Campaign metrics sync failed', [
                'campaign_id' => $this->campaignId,
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
        Log::error('SyncCampaignMetrics job failed permanently', [
            'campaign_id' => $this->campaignId,
            'error' => $exception->getMessage(),
        ]);
    }
}
