<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\ProcessRetryQueue;
use App\Jobs\SyncSystems;
use App\Models\RateLimit;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| Agendamento de tarefas periodicas do sistema de integracoes.
|
*/

/*
|--------------------------------------------------------------------------
| Retry Queue
|--------------------------------------------------------------------------
| Processa eventos que falharam e estao aguardando retry.
*/
Schedule::job(new ProcessRetryQueue(100))
    ->everyFiveMinutes()
    ->name('process-retry-queue')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Sincronizacoes Periodicas
|--------------------------------------------------------------------------
*/

// CRM -> Operacao (agenda e alocacao)
Schedule::job(new SyncSystems('crm_operacao'))
    ->hourly()
    ->name('sync-crm-operacao')
    ->withoutOverlapping();

// Operacao -> Financeiro (faturamento)
Schedule::job(new SyncSystems('operacao_financeiro'))
    ->dailyAt('23:00')
    ->name('sync-operacao-financeiro')
    ->withoutOverlapping();

// CRM -> Financeiro (setup de cobranca)
Schedule::job(new SyncSystems('crm_financeiro'))
    ->twiceDaily(6, 18)
    ->name('sync-crm-financeiro')
    ->withoutOverlapping();

// Cuidadores -> CRM (atualizacoes)
Schedule::job(new SyncSystems('cuidadores_crm'))
    ->everyFourHours()
    ->name('sync-cuidadores-crm')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Limpeza
|--------------------------------------------------------------------------
*/

// Limpa registros antigos de rate limiting
Schedule::call(function () {
    RateLimit::cleanup();
})->hourly()->name('cleanup-rate-limits');

// Limpa eventos processados antigos (mais de 30 dias)
Schedule::call(function () {
    \App\Models\IntegrationEvent::where('status_id', 3) // Done
        ->where('created_at', '<', now()->subDays(30))
        ->delete();
})->daily()->name('cleanup-old-events');
