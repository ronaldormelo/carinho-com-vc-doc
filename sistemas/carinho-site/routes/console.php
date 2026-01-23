<?php

use App\Jobs\SyncLeadToCrm;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| Comandos e tarefas agendadas do sistema.
|
*/

// ==========================================================================
// Tarefas Agendadas
// ==========================================================================

// Sincroniza leads pendentes com o CRM (a cada 5 minutos)
Schedule::call(function () {
    $pendingSubmissions = FormSubmission::notSynced()
        ->where('created_at', '>=', now()->subHours(24))
        ->limit(50)
        ->get();

    foreach ($pendingSubmissions as $submission) {
        SyncLeadToCrm::dispatch($submission);
    }
})->everyFiveMinutes()->name('sync-leads-to-crm');

// Limpa cache de paginas (diariamente as 3h)
Schedule::command('cache:clear')->dailyAt('03:00')->name('clear-cache');
