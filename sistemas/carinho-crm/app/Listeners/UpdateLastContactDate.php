<?php

namespace App\Listeners;

use App\Events\InteractionRecorded;

class UpdateLastContactDate
{
    public function handle(InteractionRecorded $event): void
    {
        $interaction = $event->interaction;
        
        // Atualiza updated_at do lead para refletir última interação
        $interaction->lead?->touch();
    }
}
