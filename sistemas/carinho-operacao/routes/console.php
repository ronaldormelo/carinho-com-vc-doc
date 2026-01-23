<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckScheduleDelays;
use App\Jobs\SendScheduleReminders;
use App\Jobs\CheckEmergencyEscalation;

/*
|--------------------------------------------------------------------------
| Console Routes - Carinho Operacao
|--------------------------------------------------------------------------
|
| Comandos agendados e rotas de console.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Verifica atrasos a cada 5 minutos
Schedule::job(new CheckScheduleDelays())
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Envia lembretes 24h antes
Schedule::job(new SendScheduleReminders(24))
    ->dailyAt('08:00')
    ->withoutOverlapping();

// Envia lembretes 2h antes
Schedule::job(new SendScheduleReminders(2))
    ->hourly()
    ->withoutOverlapping();

// Verifica escalonamento de emergencias a cada 10 minutos
Schedule::job(new CheckEmergencyEscalation())
    ->everyTenMinutes()
    ->withoutOverlapping();
