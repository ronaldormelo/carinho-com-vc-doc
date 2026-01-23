<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $serviceData
    ) {}
}
