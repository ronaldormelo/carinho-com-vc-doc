<?php

namespace App\Events;

use App\Models\Contract;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractExpiring
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Contract $contract,
        public int $daysRemaining
    ) {}
}
