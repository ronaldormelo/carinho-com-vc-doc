<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Verifica contratos próximos do vencimento - diariamente às 8h
        $schedule->job(new \App\Jobs\CheckExpiringContractsJob())
            ->dailyAt('08:00')
            ->description('Verifica contratos expirando');

        // Verifica tarefas atrasadas - a cada 4 horas
        $schedule->job(new \App\Jobs\CheckOverdueTasksJob())
            ->everyFourHours()
            ->description('Verifica tarefas atrasadas');

        // Sincroniza com sistemas externos - a cada hora
        $schedule->job(new \App\Jobs\SyncWithExternalSystemsJob())
            ->hourly()
            ->description('Sincroniza com sistemas externos');

        // Gera relatório diário - às 6h
        $schedule->job(new \App\Jobs\GenerateDailyReportJob())
            ->dailyAt('06:00')
            ->description('Gera relatório diário');

        // Limpa cache antigo - diariamente às 3h
        $schedule->command('cache:prune-stale-tags')
            ->dailyAt('03:00')
            ->description('Limpa cache antigo');

        // Limpa jobs falhos antigos - semanalmente
        $schedule->command('queue:prune-failed --hours=168')
            ->weekly()
            ->description('Limpa jobs falhos com mais de 7 dias');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
