<?php

namespace App\Jobs;

use App\Models\CaregiverRating;
use App\Integrations\Crm\CrmClient;
use App\Integrations\Integracoes\IntegracoesClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncRatingWithCrm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 60;

    public function __construct(
        private CaregiverRating $rating
    ) {
        $this->onQueue('integrations');
    }

    public function handle(CrmClient $crmClient, IntegracoesClient $integracoesClient): void
    {
        Log::info('Sincronizando avaliacao com CRM', [
            'rating_id' => $this->rating->id,
            'caregiver_id' => $this->rating->caregiver_id,
        ]);

        // Sincroniza com CRM
        $crmResult = $crmClient->syncRating([
            'caregiver_id' => $this->rating->caregiver_id,
            'service_id' => $this->rating->service_id,
            'score' => $this->rating->score,
            'comment' => $this->rating->comment,
            'created_at' => $this->rating->created_at->toIso8601String(),
        ]);

        if (!$crmResult['ok']) {
            Log::warning('Falha ao sincronizar avaliacao com CRM', [
                'rating_id' => $this->rating->id,
                'response' => $crmResult,
            ]);
        }

        // Publica evento no hub de integracoes
        $integracoesClient->ratingReceived(
            $this->rating->caregiver_id,
            $this->rating->service_id,
            $this->rating->score
        );

        Log::info('Avaliacao sincronizada com CRM', [
            'rating_id' => $this->rating->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job de sincronizacao de avaliacao falhou', [
            'rating_id' => $this->rating->id,
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return [
            'rating-sync',
            'rating:' . $this->rating->id,
            'caregiver:' . $this->rating->caregiver_id,
        ];
    }
}
