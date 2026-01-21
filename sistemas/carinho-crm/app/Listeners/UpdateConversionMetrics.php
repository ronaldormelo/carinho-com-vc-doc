<?php

namespace App\Listeners;

use App\Events\LeadLost;
use Illuminate\Support\Facades\Cache;

class UpdateConversionMetrics
{
    public function handle(LeadLost $event): void
    {
        // Invalida caches de métricas de conversão
        Cache::forget('reports:dashboard');
        
        $today = now()->format('Y-m-d');
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        Cache::forget("reports:conversion:{$startOfMonth}:{$today}:day");
        Cache::forget("reports:conversion:{$startOfMonth}:{$today}:week");
        Cache::forget("reports:conversion:{$startOfMonth}:{$today}:month");
    }
}
