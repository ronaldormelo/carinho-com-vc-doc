<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Carinho Financeiro
|--------------------------------------------------------------------------
*/

// Health checks
Route::get('/health', [HealthController::class, 'index']);
Route::get('/health/detailed', [HealthController::class, 'detailed']);

// Webhooks (sem autenticação - validação por assinatura)
Route::prefix('webhooks')->group(function () {
    // Webhook do Stripe
    Route::post('/stripe', [WebhookController::class, 'stripe'])
        ->withoutMiddleware(['web']);

    // Webhook interno (requer token)
    Route::post('/internal', [WebhookController::class, 'internal'])
        ->middleware(['verify.internal.token']);
});
