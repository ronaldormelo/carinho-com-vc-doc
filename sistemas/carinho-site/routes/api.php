<?php

use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ContentController;
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

    // ======================================================================
    // Gestão de Conteúdo (chamado pelo CRM)
    // ======================================================================

    Route::prefix('content')->group(function () {
        // Testimonials
        Route::get('/testimonials', [ContentController::class, 'testimonials']);
        Route::get('/testimonials/{id}', [ContentController::class, 'testimonial']);
        Route::post('/testimonials', [ContentController::class, 'createTestimonial']);
        Route::put('/testimonials/{id}', [ContentController::class, 'updateTestimonial']);
        Route::delete('/testimonials/{id}', [ContentController::class, 'deleteTestimonial']);

        // FAQ Categories
        Route::get('/faq/categories', [ContentController::class, 'faqCategories']);
        Route::get('/faq/categories/{id}', [ContentController::class, 'faqCategory']);
        Route::post('/faq/categories', [ContentController::class, 'createFaqCategory']);
        Route::put('/faq/categories/{id}', [ContentController::class, 'updateFaqCategory']);
        Route::delete('/faq/categories/{id}', [ContentController::class, 'deleteFaqCategory']);

        // FAQ Items
        Route::get('/faq/categories/{categoryId}/items', [ContentController::class, 'faqItems']);
        Route::get('/faq/categories/{categoryId}/items/{itemId}', [ContentController::class, 'faqItem']);
        Route::post('/faq/categories/{categoryId}/items', [ContentController::class, 'createFaqItem']);
        Route::put('/faq/categories/{categoryId}/items/{itemId}', [ContentController::class, 'updateFaqItem']);
        Route::delete('/faq/categories/{categoryId}/items/{itemId}', [ContentController::class, 'deleteFaqItem']);

        // Pages
        Route::get('/pages', [ContentController::class, 'pages']);
        Route::get('/pages/{id}', [ContentController::class, 'page']);
        Route::post('/pages', [ContentController::class, 'createPage']);
        Route::put('/pages/{id}', [ContentController::class, 'updatePage']);
        Route::delete('/pages/{id}', [ContentController::class, 'deletePage']);
    });

});
