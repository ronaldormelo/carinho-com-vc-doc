<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LogDealActivity
{
    public function handle($event): void
    {
        $deal = $event->deal;
        $eventClass = class_basename($event);

        Log::channel('audit')->info("Deal Activity: {$eventClass}", [
            'deal_id' => $deal->id,
            'lead_id' => $deal->lead_id,
            'stage_id' => $deal->stage_id,
            'status_id' => $deal->status_id,
            'value' => $deal->value_estimated,
            'previous_stage_id' => $event->previousStageId ?? null,
        ]);

        // Invalida cache do pipeline
        Cache::forget('pipeline:board');
    }
}
