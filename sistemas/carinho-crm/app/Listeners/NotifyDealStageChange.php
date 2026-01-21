<?php

namespace App\Listeners;

use App\Events\DealStageChanged;
use App\Services\Integrations\CarinhoAtendimentoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyDealStageChange implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'crm-integrations';

    public function __construct(
        protected CarinhoAtendimentoService $atendimentoService
    ) {}

    public function handle(DealStageChanged $event): void
    {
        $deal = $event->deal;
        $deal->load(['lead', 'stage']);

        if (!$deal->lead) {
            return;
        }

        $this->atendimentoService->syncInteraction($deal->lead->id, [
            'channel' => 'system',
            'direction' => 'internal',
            'summary' => "Deal movido para estágio: {$deal->stage->name}",
        ]);
    }
}
