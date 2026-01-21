<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhook\ZApiWebhookController;
use App\Http\Controllers\Webhook\InternalWebhookController;

/*
|--------------------------------------------------------------------------
| Webhook Routes - Carinho CRM
|--------------------------------------------------------------------------
|
| Rotas para receber webhooks de sistemas externos e internos
| Prefixo: /webhooks
|
*/

// Z-API (WhatsApp)
Route::prefix('zapi')->middleware('throttle:webhooks')->group(function () {
    Route::post('message', [ZApiWebhookController::class, 'message'])->name('webhooks.zapi.message');
    Route::post('status', [ZApiWebhookController::class, 'status'])->name('webhooks.zapi.status');
    Route::post('connection', [ZApiWebhookController::class, 'connection'])->name('webhooks.zapi.connection');
});

// Sistemas internos Carinho
Route::prefix('internal')->middleware(['throttle:webhooks', 'verify.internal'])->group(function () {
    // Site - novo lead do formulário
    Route::post('site/lead', [InternalWebhookController::class, 'siteNewLead'])->name('webhooks.site.lead');
    
    // Atendimento - atualização de status
    Route::post('atendimento/status', [InternalWebhookController::class, 'atendimentoStatus'])->name('webhooks.atendimento.status');
    Route::post('atendimento/interaction', [InternalWebhookController::class, 'atendimentoInteraction'])->name('webhooks.atendimento.interaction');
    
    // Operação - notificações
    Route::post('operacao/service-started', [InternalWebhookController::class, 'operacaoServiceStarted'])->name('webhooks.operacao.started');
    Route::post('operacao/service-completed', [InternalWebhookController::class, 'operacaoServiceCompleted'])->name('webhooks.operacao.completed');
    
    // Financeiro - atualizações de pagamento
    Route::post('financeiro/payment', [InternalWebhookController::class, 'financeiroPayment'])->name('webhooks.financeiro.payment');
    
    // Marketing - UTM tracking
    Route::post('marketing/utm', [InternalWebhookController::class, 'marketingUtm'])->name('webhooks.marketing.utm');
    
    // Documentos - assinatura de contrato
    Route::post('documentos/contract-signed', [InternalWebhookController::class, 'documentosContractSigned'])->name('webhooks.documentos.signed');
});
