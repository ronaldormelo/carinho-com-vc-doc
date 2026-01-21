<?php

namespace App\Listeners;

use App\Events\InteractionRecorded;
use Illuminate\Support\Facades\Log;

class LogInteractionActivity
{
    public function handle(InteractionRecorded $event): void
    {
        $interaction = $event->interaction;

        Log::channel('audit')->info('Interaction Recorded', [
            'interaction_id' => $interaction->id,
            'lead_id' => $interaction->lead_id,
            'channel_id' => $interaction->channel_id,
            'occurred_at' => $interaction->occurred_at?->toIso8601String(),
        ]);
    }
}
