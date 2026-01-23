<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;

class LogLeadActivity
{
    public function handle($event): void
    {
        $lead = $event->lead;
        $eventClass = class_basename($event);

        Log::channel('audit')->info("Lead Activity: {$eventClass}", [
            'lead_id' => $lead->id,
            'lead_name' => $lead->name,
            'status_id' => $lead->status_id,
            'previous_status_id' => $event->previousStatusId ?? null,
        ]);
    }
}
