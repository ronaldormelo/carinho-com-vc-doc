<?php

use App\Http\Controllers\EmailController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\ScriptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'show']);

Route::post('/webhooks/whatsapp/z-api', [WebhookController::class, 'whatsapp']);

Route::middleware(['internal.token'])->group(function () {
    // Inbox e conversas
    Route::get('/inbox', [InboxController::class, 'index']);
    Route::get('/inbox/at-risk', [InboxController::class, 'getAtRisk']);
    Route::get('/inbox/{conversation}', [InboxController::class, 'show']);
    Route::patch('/inbox/{conversation}/status', [InboxController::class, 'updateStatus']);
    Route::post('/inbox/{conversation}/tags', [InboxController::class, 'addTags']);
    Route::post('/inbox/{conversation}/incident', [InboxController::class, 'createIncident']);
    Route::post('/inbox/{conversation}/escalate', [InboxController::class, 'escalate']);
    Route::post('/inbox/{conversation}/notes', [InboxController::class, 'addNote']);
    
    // Triagem
    Route::get('/triage/checklist', [InboxController::class, 'getTriageChecklist']);
    Route::post('/inbox/{conversation}/triage', [InboxController::class, 'saveTriage']);
    
    // Scripts sugeridos
    Route::get('/inbox/{conversation}/scripts', [InboxController::class, 'getSuggestedScripts']);
    
    // Motivos de perda
    Route::get('/loss-reasons', [InboxController::class, 'getLossReasons']);
    
    // SLA
    Route::get('/sla/alerts', [InboxController::class, 'getSlaAlerts']);
    Route::post('/sla/alerts/{alertId}/acknowledge', [InboxController::class, 'acknowledgeSlaAlert']);
    
    // Scripts de comunicação
    Route::get('/scripts', [ScriptController::class, 'index']);
    Route::get('/scripts/categories', [ScriptController::class, 'categories']);
    Route::get('/scripts/search', [ScriptController::class, 'search']);
    Route::get('/scripts/{code}', [ScriptController::class, 'show']);
    Route::post('/scripts/{code}/render', [ScriptController::class, 'render']);
    
    // Relatórios e estatísticas
    Route::get('/reports/funnel', [ReportController::class, 'funnelStats']);
    Route::get('/reports/loss', [ReportController::class, 'lossStats']);
    Route::get('/reports/sla', [ReportController::class, 'slaStats']);
    Route::get('/reports/agent/{agentId}/actions', [ReportController::class, 'agentActions']);
    
    // Mensagens
    Route::post('/conversations/{conversation}/messages', [MessagesController::class, 'store']);
    Route::post('/conversations/{conversation}/proposal-email', [EmailController::class, 'sendProposal']);
    Route::post('/conversations/{conversation}/contract-email', [EmailController::class, 'sendContract']);
});
