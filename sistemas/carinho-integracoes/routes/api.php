<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\MappingController;
use App\Http\Controllers\Api\WebhookEndpointController;
use App\Http\Controllers\Api\DeadLetterController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rotas da API do sistema de integracoes.
| Todas as rotas requerem autenticacao via API Key.
|
*/

// Rotas protegidas por API Key
Route::middleware(['api.auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Eventos
    |--------------------------------------------------------------------------
    */
    Route::prefix('events')->group(function () {
        Route::get('/', [EventController::class, 'index']);
        Route::post('/', [EventController::class, 'store']);
        Route::get('/stats', [EventController::class, 'stats']);
        Route::get('/{id}', [EventController::class, 'show']);
        Route::post('/{id}/retry', [EventController::class, 'retry']);
    });

    /*
    |--------------------------------------------------------------------------
    | Sincronizacao
    |--------------------------------------------------------------------------
    */
    Route::prefix('sync')->group(function () {
        Route::get('/jobs', [SyncController::class, 'index']);
        Route::post('/start', [SyncController::class, 'start']);
        Route::get('/stats', [SyncController::class, 'stats']);
        Route::get('/jobs/{id}', [SyncController::class, 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | Mapeamentos
    |--------------------------------------------------------------------------
    */
    Route::prefix('mappings')->group(function () {
        Route::get('/', [MappingController::class, 'index']);
        Route::post('/', [MappingController::class, 'store']);
        Route::post('/test', [MappingController::class, 'test']);
        Route::get('/{eventType}/{targetSystem}', [MappingController::class, 'show']);
        Route::get('/{eventType}/{targetSystem}/versions', [MappingController::class, 'versions']);
    });

    /*
    |--------------------------------------------------------------------------
    | Endpoints de Webhook
    |--------------------------------------------------------------------------
    */
    Route::prefix('endpoints')->group(function () {
        Route::get('/', [WebhookEndpointController::class, 'index']);
        Route::post('/', [WebhookEndpointController::class, 'store']);
        Route::get('/{id}', [WebhookEndpointController::class, 'show']);
        Route::put('/{id}', [WebhookEndpointController::class, 'update']);
        Route::post('/{id}/activate', [WebhookEndpointController::class, 'activate']);
        Route::post('/{id}/deactivate', [WebhookEndpointController::class, 'deactivate']);
        Route::post('/{id}/rotate-secret', [WebhookEndpointController::class, 'rotateSecret']);
    });

    /*
    |--------------------------------------------------------------------------
    | Dead Letter Queue
    |--------------------------------------------------------------------------
    */
    Route::prefix('dlq')->group(function () {
        Route::get('/', [DeadLetterController::class, 'index']);
        Route::get('/stats', [DeadLetterController::class, 'stats']);
        Route::get('/{id}', [DeadLetterController::class, 'show']);
        Route::post('/{id}/retry', [DeadLetterController::class, 'retry']);
        Route::post('/{id}/archive', [DeadLetterController::class, 'archive']);
        Route::delete('/{id}', [DeadLetterController::class, 'destroy']);
        Route::post('/retry-all', [DeadLetterController::class, 'retryAll']);
    });
});
