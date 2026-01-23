<?php

use App\Jobs\CleanExpiredDocuments;
use App\Jobs\ProcessRetentionPolicies;
use App\Jobs\SyncDocumentsWithStorage;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| Sistema: Carinho Documentos e LGPD
| Comandos agendados para manutencao e conformidade LGPD.
|
*/

// Executar politicas de retencao diariamente as 3h
Schedule::job(new ProcessRetentionPolicies)->dailyAt('03:00');

// Limpar documentos expirados/marcados para exclusao diariamente as 4h
Schedule::job(new CleanExpiredDocuments)->dailyAt('04:00');

// Sincronizar metadados com storage a cada hora
Schedule::job(new SyncDocumentsWithStorage)->hourly();
