<?php

namespace App\Listeners;

use App\Events\LeadStatusChanged;
use Illuminate\Support\Facades\Cache;

class UpdatePipelineMetrics
{
    public function handle(LeadStatusChanged $event): void
    {
        // Invalida caches relacionados ao pipeline
        Cache::forget('pipeline:board');
        Cache::forget('leads:statistics');
        Cache::forget('reports:dashboard');

        // Invalida métricas por período
        $today = now()->format('Y-m-d');
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        Cache::forget("pipeline:metrics:{$startOfMonth}:{$today}");
    }
}
