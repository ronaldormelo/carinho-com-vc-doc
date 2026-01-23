<?php

use App\Http\Controllers\EmailController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\TriageController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'show']);

Route::post('/webhooks/whatsapp/z-api', [WebhookController::class, 'whatsapp']);

Route::middleware(['internal.token'])->group(function () {
    // Inbox e conversas
    Route::get('/inbox', [InboxController::class, 'index']);
    Route::get('/inbox/{conversation}', [InboxController::class, 'show']);
    Route::patch('/inbox/{conversation}/status', [InboxController::class, 'updateStatus']);
    Route::post('/inbox/{conversation}/lost', [InboxController::class, 'markAsLost']);
    Route::post('/inbox/{conversation}/tags', [InboxController::class, 'addTags']);
    Route::post('/inbox/{conversation}/incident', [InboxController::class, 'createIncident']);
    Route::get('/inbox/{conversation}/history', [InboxController::class, 'history']);
    Route::post('/inbox/{conversation}/notes', [InboxController::class, 'addNote']);
    Route::post('/inbox/{conversation}/escalate', [InboxController::class, 'escalate']);
    Route::post('/inbox/{conversation}/deescalate', [InboxController::class, 'deescalate']);
    Route::post('/inbox/{conversation}/satisfaction', [InboxController::class, 'recordSatisfaction']);

    // Mensagens
    Route::post('/conversations/{conversation}/messages', [MessagesController::class, 'store']);
    Route::post('/conversations/{conversation}/proposal-email', [EmailController::class, 'sendProposal']);
    Route::post('/conversations/{conversation}/contract-email', [EmailController::class, 'sendContract']);

    // Triagem
    Route::get('/triage/checklist', [TriageController::class, 'checklist']);
    Route::get('/triage/script', [TriageController::class, 'script']);
    Route::get('/triage/{conversation}/status', [TriageController::class, 'status']);
    Route::post('/triage/{conversation}/response', [TriageController::class, 'saveResponse']);
    Route::post('/triage/{conversation}/responses', [TriageController::class, 'saveResponses']);
    Route::get('/triage/{conversation}/summary', [TriageController::class, 'summary']);

    // Metricas e dashboard
    Route::get('/metrics/dashboard', [MetricsController::class, 'dashboard']);
    Route::get('/metrics/sla', [MetricsController::class, 'sla']);
    Route::get('/metrics/funnel', [MetricsController::class, 'funnel']);
    Route::get('/metrics/incidents', [MetricsController::class, 'incidents']);
    Route::get('/metrics/satisfaction', [MetricsController::class, 'satisfaction']);
    Route::get('/metrics/escalations', [MetricsController::class, 'escalations']);
    Route::get('/metrics/working-hours', [MetricsController::class, 'workingHours']);
    Route::get('/metrics/holidays', [MetricsController::class, 'holidays']);
});
