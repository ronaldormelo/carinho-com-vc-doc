<?php

use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Carinho Financeiro
|--------------------------------------------------------------------------
|
| Rotas da API do sistema financeiro.
| Todas as rotas requerem autenticação via token interno.
|
*/

Route::middleware(['verify.internal.token'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Faturas (Invoices)
    |--------------------------------------------------------------------------
    */
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::post('/', [InvoiceController::class, 'store']);
        Route::get('/overdue', [InvoiceController::class, 'overdue']);
        Route::get('/due-soon', [InvoiceController::class, 'dueSoon']);
        Route::get('/client/{clientId}/summary', [InvoiceController::class, 'clientSummary']);
        Route::get('/{invoice}', [InvoiceController::class, 'show']);
        Route::post('/{invoice}/items', [InvoiceController::class, 'addItem']);
        Route::delete('/{invoice}/items/{itemId}', [InvoiceController::class, 'removeItem']);
        Route::post('/{invoice}/discount', [InvoiceController::class, 'applyDiscount']);
        Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel']);
    });

    /*
    |--------------------------------------------------------------------------
    | Pagamentos (Payments)
    |--------------------------------------------------------------------------
    */
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::get('/{payment}', [PaymentController::class, 'show']);
        Route::get('/{payment}/status', [PaymentController::class, 'checkStatus']);
        Route::post('/{payment}/refund', [PaymentController::class, 'refund']);
        Route::post('/invoice/{invoice}/generate-link', [PaymentController::class, 'generateLink']);
        Route::get('/invoice/{invoiceId}', [PaymentController::class, 'invoicePayments']);
    });

    /*
    |--------------------------------------------------------------------------
    | Repasses (Payouts)
    |--------------------------------------------------------------------------
    */
    Route::prefix('payouts')->group(function () {
        Route::get('/', [PayoutController::class, 'index']);
        Route::post('/', [PayoutController::class, 'store']);
        Route::get('/ready', [PayoutController::class, 'readyToProcess']);
        Route::post('/generate', [PayoutController::class, 'generateForPeriod']);
        Route::post('/process-all', [PayoutController::class, 'processAll']);
        Route::get('/caregiver/{caregiverId}/summary', [PayoutController::class, 'caregiverSummary']);
        Route::get('/commission/{serviceTypeId}', [PayoutController::class, 'commissionDetails']);
        Route::get('/{payout}', [PayoutController::class, 'show']);
        Route::post('/{payout}/process', [PayoutController::class, 'process']);
        Route::post('/{payout}/cancel', [PayoutController::class, 'cancel']);
    });

    /*
    |--------------------------------------------------------------------------
    | Precificação (Pricing)
    |--------------------------------------------------------------------------
    */
    Route::prefix('pricing')->group(function () {
        Route::post('/calculate', [PricingController::class, 'calculate']);
        Route::post('/simulate', [PricingController::class, 'simulate']);
        Route::post('/margin', [PricingController::class, 'margin']);
        Route::post('/minimum-viable', [PricingController::class, 'minimumViable']);
        Route::get('/service-types', [PricingController::class, 'serviceTypes']);
        Route::get('/cancellation-policy', [PricingController::class, 'cancellationPolicy']);
        Route::post('/simulate-cancellation', [PricingController::class, 'simulateCancellation']);
        Route::get('/commission-config', [PricingController::class, 'commissionConfig']);
        Route::get('/payment-config', [PricingController::class, 'paymentConfig']);

        // Planos de preço
        Route::get('/plans', [PricingController::class, 'plans']);
        Route::post('/plans', [PricingController::class, 'storePlan']);
        Route::put('/plans/{plan}', [PricingController::class, 'updatePlan']);
        Route::post('/plans/{plan}/rules', [PricingController::class, 'addRule']);
        Route::delete('/plans/{plan}/rules/{rule}', [PricingController::class, 'removeRule']);
    });

    /*
    |--------------------------------------------------------------------------
    | Conciliação (Reconciliation)
    |--------------------------------------------------------------------------
    */
    Route::prefix('reconciliation')->group(function () {
        Route::get('/', [ReconciliationController::class, 'index']);
        Route::post('/process', [ReconciliationController::class, 'process']);
        Route::get('/cash-flow', [ReconciliationController::class, 'cashFlow']);
        Route::get('/indicators', [ReconciliationController::class, 'indicators']);
        Route::get('/unreconciled-invoices', [ReconciliationController::class, 'unreconciledInvoices']);
        Route::get('/orphan-payments', [ReconciliationController::class, 'orphanPayments']);
        Route::get('/{period}', [ReconciliationController::class, 'show']);
        Route::post('/{reconciliation}/close', [ReconciliationController::class, 'close']);
    });

    /*
    |--------------------------------------------------------------------------
    | Contas Bancárias (Bank Accounts)
    |--------------------------------------------------------------------------
    */
    Route::prefix('bank-accounts')->group(function () {
        Route::get('/', [BankAccountController::class, 'index']);
        Route::post('/', [BankAccountController::class, 'store']);
        Route::get('/{bankAccount}', [BankAccountController::class, 'show']);
        Route::post('/{bankAccount}/set-default', [BankAccountController::class, 'setDefault']);
        Route::post('/{bankAccount}/verify', [BankAccountController::class, 'verify']);
        Route::delete('/{bankAccount}', [BankAccountController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Configurações (Settings)
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->group(function () {
        // Listagem
        Route::get('/', [SettingController::class, 'index']);
        Route::get('/categories', [SettingController::class, 'categories']);
        Route::get('/category/{categoryCode}', [SettingController::class, 'byCategory']);
        Route::get('/public', [SettingController::class, 'publicSettings']);
        Route::get('/history', [SettingController::class, 'allHistory']);
        
        // Configurações específicas por área
        Route::get('/config/payment', [SettingController::class, 'paymentConfig']);
        Route::get('/config/cancellation', [SettingController::class, 'cancellationConfig']);
        Route::get('/config/commission', [SettingController::class, 'commissionConfig']);
        Route::get('/config/pricing', [SettingController::class, 'pricingConfig']);
        Route::get('/config/margin', [SettingController::class, 'marginConfig']);
        Route::get('/config/payout', [SettingController::class, 'payoutConfig']);
        
        // Operações de uma configuração
        Route::get('/{key}', [SettingController::class, 'show']);
        Route::put('/{key}', [SettingController::class, 'update']);
        Route::post('/{key}/restore', [SettingController::class, 'restoreDefault']);
        Route::get('/{key}/history', [SettingController::class, 'history']);
        
        // Operações em lote
        Route::put('/', [SettingController::class, 'updateMany']);
        Route::post('/category/{categoryCode}/restore', [SettingController::class, 'restoreCategoryDefaults']);
        Route::post('/cache/clear', [SettingController::class, 'clearCache']);
    });
});
