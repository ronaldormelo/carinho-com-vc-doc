<?php

use App\Jobs\ProcessMonthlyReconciliation;
use App\Jobs\ProcessOverdueInvoices;
use App\Jobs\ProcessWeeklyPayouts;
use App\Jobs\SendDueReminders;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes - Scheduled Tasks
|--------------------------------------------------------------------------
|
| Tarefas agendadas do sistema financeiro.
|
*/

// Diário: Processa faturas vencidas (executa às 6h)
Schedule::job(new ProcessOverdueInvoices)
    ->dailyAt('06:00')
    ->name('process-overdue-invoices')
    ->withoutOverlapping();

// Diário: Envia lembretes de vencimento (executa às 9h)
Schedule::job(new SendDueReminders(3))
    ->dailyAt('09:00')
    ->name('send-due-reminders-3days')
    ->withoutOverlapping();

// Diário: Envia lembretes de vencimento - 1 dia antes (executa às 9h)
Schedule::job(new SendDueReminders(1))
    ->dailyAt('09:00')
    ->name('send-due-reminders-1day')
    ->withoutOverlapping();

// Semanal: Processa repasses (sextas-feiras às 10h)
Schedule::job(new ProcessWeeklyPayouts)
    ->weeklyOn(5, '10:00')
    ->name('process-weekly-payouts')
    ->withoutOverlapping();

// Mensal: Conciliação do mês anterior (dia 5 às 8h)
Schedule::call(function () {
    $lastMonth = now()->subMonth();
    ProcessMonthlyReconciliation::dispatch($lastMonth->year, $lastMonth->month);
})
    ->monthlyOn(5, '08:00')
    ->name('process-monthly-reconciliation')
    ->withoutOverlapping();
