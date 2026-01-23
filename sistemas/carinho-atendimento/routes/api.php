<?php

use App\Http\Controllers\EmailController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'show']);

Route::post('/webhooks/whatsapp/z-api', [WebhookController::class, 'whatsapp']);

Route::middleware(['internal.token'])->group(function () {
    Route::get('/inbox', [InboxController::class, 'index']);
    Route::get('/inbox/{conversation}', [InboxController::class, 'show']);
    Route::patch('/inbox/{conversation}/status', [InboxController::class, 'updateStatus']);
    Route::post('/inbox/{conversation}/tags', [InboxController::class, 'addTags']);
    Route::post('/inbox/{conversation}/incident', [InboxController::class, 'createIncident']);
    Route::post('/conversations/{conversation}/messages', [MessagesController::class, 'store']);
    Route::post('/conversations/{conversation}/proposal-email', [EmailController::class, 'sendProposal']);
    Route::post('/conversations/{conversation}/contract-email', [EmailController::class, 'sendContract']);
});
