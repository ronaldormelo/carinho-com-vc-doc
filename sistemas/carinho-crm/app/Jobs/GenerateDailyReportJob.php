<?php

namespace App\Jobs;

use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GenerateDailyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'crm-reports';
    public int $timeout = 180;

    public function handle(ReportService $reportService): void
    {
        Log::info('Gerando relatório diário');

        $yesterday = now()->subDay()->toDateString();
        $today = now()->toDateString();

        // Gera e cacheia relatórios para o dia
        $dashboard = $reportService->getDashboardData();
        Cache::put('reports:dashboard', $dashboard, now()->addHours(12));

        $conversion = $reportService->getConversionReport(
            now()->startOfMonth()->toDateString(),
            $today,
            'day'
        );
        Cache::put("reports:conversion:{$yesterday}:{$today}:day", $conversion, now()->addHours(12));

        $leadSources = $reportService->getLeadSourcesReport(
            now()->startOfMonth()->toDateString(),
            $today
        );
        Cache::put("reports:lead-sources", $leadSources, now()->addHours(12));

        Log::info('Relatório diário gerado com sucesso');
    }
}
