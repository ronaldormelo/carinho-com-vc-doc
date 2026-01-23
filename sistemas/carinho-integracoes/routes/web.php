<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Webhook\WhatsAppWebhookController;
use App\Http\Controllers\Webhook\SystemWebhookController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Rotas web para health checks e webhooks.
| Webhooks nao usam autenticacao por API Key, mas validam assinatura.
|
*/

/*
|--------------------------------------------------------------------------
| Health Checks e Monitoramento
|--------------------------------------------------------------------------
|
| Endpoints para verificação de saúde e monitoramento operacional.
| Não requerem autenticação para permitir acesso de load balancers.
|
*/

// Health checks básicos
Route::get('/health', [HealthController::class, 'check']);
Route::get('/health/detailed', [HealthController::class, 'detailed']);
Route::get('/status', [HealthController::class, 'status']);

// Dashboard e alertas operacionais
Route::get('/dashboard', [HealthController::class, 'dashboard']);
Route::get('/alerts', [HealthController::class, 'alerts']);
Route::get('/report/daily', [HealthController::class, 'dailyReport']);

// Circuit breakers
Route::get('/circuit-breakers', [HealthController::class, 'circuitBreakers']);
Route::post('/circuit-breakers/{service}/reset', [HealthController::class, 'resetCircuitBreaker']);

/*
|--------------------------------------------------------------------------
| Webhooks - WhatsApp (Z-API)
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks/whatsapp')->group(function () {
    Route::get('/', [WhatsAppWebhookController::class, 'verify']);
    Route::post('/', [WhatsAppWebhookController::class, 'handle']);
    Route::post('/status', [WhatsAppWebhookController::class, 'status']);
});

/*
|--------------------------------------------------------------------------
| Webhooks - Site
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks/site')->group(function () {
    Route::post('/lead', [SystemWebhookController::class, 'siteLead']);
});

/*
|--------------------------------------------------------------------------
| Webhooks - CRM
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks/crm')->group(function () {
    Route::post('/client-registered', [SystemWebhookController::class, 'crmClientRegistered']);
});

/*
|--------------------------------------------------------------------------
| Webhooks - Operacao
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks/operacao')->group(function () {
    Route::post('/service-completed', [SystemWebhookController::class, 'operacaoServiceCompleted']);
});

/*
|--------------------------------------------------------------------------
| Webhooks - Financeiro
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks/financeiro')->group(function () {
    Route::post('/payment', [SystemWebhookController::class, 'financeiroPayment']);
    Route::post('/payout', [SystemWebhookController::class, 'financeiroPayout']);
});

/*
|--------------------------------------------------------------------------
| Webhooks - Cuidadores
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks/cuidadores')->group(function () {
    Route::post('/feedback', [SystemWebhookController::class, 'cuidadoresFeedback']);
});

/*
|--------------------------------------------------------------------------
| Webhook Generico por Sistema
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/systems/{system}', [SystemWebhookController::class, 'handle']);
