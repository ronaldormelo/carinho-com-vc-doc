<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Domain\DomainCampaignStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para sincronizar metricas de todas as campanhas ativas.
 */
class SyncAllCampaignsMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero de tentativas.
     */
    public int $tries = 1;

    /**
     * Tempo limite em segundos.
     */
    public int $timeout = 600;

    public function __construct(
        private ?string $startDate = null,
        private ?string $endDate = null
    ) {
        $this->startDate = $startDate ?? now()->subDays(7)->toDateString();
        $this->endDate = $endDate ?? now()->toDateString();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $campaigns = Campaign::whereIn('status_id', [
                DomainCampaignStatus::ACTIVE,
                DomainCampaignStatus::PAUSED,
            ])->get();

            Log::info('Starting sync for all active campaigns', [
                'total_campaigns' => $campaigns->count(),
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
            ]);

            foreach ($campaigns as $campaign) {
                // Dispara job individual para cada campanha
                SyncCampaignMetrics::dispatch(
                    $campaign->id,
                    $this->startDate,
                    $this->endDate
                )->onQueue('metrics');
            }

            Log::info('All campaign sync jobs dispatched');

        } catch (\Throwable $e) {
            Log::error('SyncAllCampaignsMetrics failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
