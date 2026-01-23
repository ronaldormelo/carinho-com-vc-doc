<?php

namespace App\Events;

use App\Models\Payout;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayoutCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Payout $payout
    ) {}
}
