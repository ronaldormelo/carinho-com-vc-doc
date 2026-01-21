<?php

namespace App\Listeners;

use App\Events\LeadLost;
use Illuminate\Support\Facades\Cache;

class RecordLossReason
{
    public function handle(LeadLost $event): void
    {
        // Invalida cache de estatísticas
        Cache::forget('leads:statistics');
        Cache::forget('reports:dashboard');
    }
}
