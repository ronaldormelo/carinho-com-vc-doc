<?php

namespace App\Events;

use App\Models\IntegrationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IntegrationEventCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public IntegrationEvent $integrationEvent
    ) {}
}
