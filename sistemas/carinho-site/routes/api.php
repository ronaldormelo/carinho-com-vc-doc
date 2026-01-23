<?php

use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API interna para integracao com outros sistemas do ecossistema Carinho.
| Todas as rotas requerem autenticacao via token interno.
|
*/

// ==========================================================================
// Health Check (publico)
// ==========================================================================

Route::get('/health', [HealthController::class, 'check']);

// ==========================================================================
// Rotas Autenticadas
// ==========================================================================

Route::middleware('throttle:60,1')->group(function () {

    // ======================================================================
    // Leads
    // ======================================================================

    Route::prefix('leads')->group(function () {
        Route::get('/', [LeadController::class, 'index']);
        Route::get('/stats', [LeadController::class, 'stats']);
        Route::get('/{id}', [LeadController::class, 'show']);
        Route::post('/{id}/mark-synced', [LeadController::class, 'markSynced']);
    });

    // ======================================================================
    // Webhooks de Sistemas Internos
    // ======================================================================

    Route::prefix('webhooks')->group(function () {
        Route::post('/crm', [WebhookController::class, 'crmUpdate']);
        Route::post('/cache/pages/clear', [WebhookController::class, 'clearPageCache']);
        Route::post('/cache/legal/clear', [WebhookController::class, 'clearLegalCache']);
    });

    // ======================================================================
    // Dominios (valores de referencia)
    // ======================================================================

    Route::get('/domains', function () {
        return response()->json([
            'page_status' => \App\Models\Domain\DomainPageStatus::all(),
            'form_target' => \App\Models\Domain\DomainFormTarget::all(),
            'urgency_level' => \App\Models\Domain\DomainUrgencyLevel::all(),
            'service_type' => \App\Models\Domain\DomainServiceType::all(),
            'legal_doc_type' => \App\Models\Domain\DomainLegalDocType::all(),
        ]);
    });

    // ======================================================================
    // Configuracoes do Site
    // ======================================================================

    Route::get('/settings', function () {
        return response()->json([
            'service_types' => config('site.service_types'),
            'urgency_levels' => config('site.urgency_levels'),
            'payment_policy' => config('site.payment_policy'),
            'cancellation_policy' => config('site.cancellation_policy'),
            'caregiver_commission' => config('site.caregiver_commission'),
            'payout_policy' => config('site.payout_policy'),
            'emergency_policy' => config('site.emergency_policy'),
            'sla' => config('site.sla'),
        ]);
    });

});
