<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\PipelineController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\InteractionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\DomainController;

/*
|--------------------------------------------------------------------------
| API Routes - Carinho CRM
|--------------------------------------------------------------------------
|
| Rotas da API v1 do sistema CRM
| Prefixo: /api/v1
|
*/

// Rotas públicas (webhooks de sistemas internos)
Route::prefix('public')->group(function () {
    // Receber lead do site
    Route::post('leads', [LeadController::class, 'store'])
        ->middleware('throttle:webhooks')
        ->name('public.leads.store');
});

// Rotas autenticadas
Route::middleware(['auth:sanctum'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Domínios (valores de referência)
    |--------------------------------------------------------------------------
    */
    Route::prefix('domains')->group(function () {
        Route::get('/', [DomainController::class, 'all'])->name('domains.all');
        Route::get('urgency-levels', [DomainController::class, 'urgencyLevels'])->name('domains.urgency');
        Route::get('service-types', [DomainController::class, 'serviceTypes'])->name('domains.services');
        Route::get('lead-statuses', [DomainController::class, 'leadStatuses'])->name('domains.lead-statuses');
        Route::get('deal-statuses', [DomainController::class, 'dealStatuses'])->name('domains.deal-statuses');
        Route::get('contract-statuses', [DomainController::class, 'contractStatuses'])->name('domains.contract-statuses');
        Route::get('interaction-channels', [DomainController::class, 'interactionChannels'])->name('domains.channels');
        Route::get('patient-types', [DomainController::class, 'patientTypes'])->name('domains.patient-types');
        Route::get('task-statuses', [DomainController::class, 'taskStatuses'])->name('domains.task-statuses');
        Route::get('consent-types', [DomainController::class, 'consentTypes'])->name('domains.consent-types');
        Route::get('loss-reasons', [DomainController::class, 'lossReasons'])->name('domains.loss-reasons');
    });

    /*
    |--------------------------------------------------------------------------
    | Leads
    |--------------------------------------------------------------------------
    */
    Route::prefix('leads')->group(function () {
        Route::get('/', [LeadController::class, 'index'])->name('leads.index');
        Route::post('/', [LeadController::class, 'store'])->name('leads.store');
        Route::get('search', [LeadController::class, 'search'])->name('leads.search');
        Route::get('statistics', [LeadController::class, 'statistics'])->name('leads.statistics');
        Route::get('{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::put('{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::delete('{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
        Route::post('{lead}/advance', [LeadController::class, 'advanceStatus'])->name('leads.advance');
        Route::post('{lead}/lost', [LeadController::class, 'markAsLost'])->name('leads.lost');
    });

    /*
    |--------------------------------------------------------------------------
    | Clientes
    |--------------------------------------------------------------------------
    */
    Route::prefix('clients')->group(function () {
        Route::get('/', [ClientController::class, 'index'])->name('clients.index');
        Route::post('/', [ClientController::class, 'store'])->name('clients.store');
        Route::get('needs-review', [ClientController::class, 'needsReview'])->name('clients.needs-review');
        Route::get('high-priority', [ClientController::class, 'highPriority'])->name('clients.high-priority');
        Route::get('churn-risk', [ClientController::class, 'churnRisk'])->name('clients.churn-risk');
        Route::get('promoters', [ClientController::class, 'promoters'])->name('clients.promoters');
        Route::get('{client}', [ClientController::class, 'show'])->name('clients.show');
        Route::put('{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
        Route::post('{client}/care-needs', [ClientController::class, 'addCareNeed'])->name('clients.care-needs');
        Route::post('{client}/consents', [ClientController::class, 'addConsent'])->name('clients.consents.store');
        Route::get('{client}/consents', [ClientController::class, 'consents'])->name('clients.consents.index');
        Route::get('{client}/history', [ClientController::class, 'history'])->name('clients.history');
        // Classificação ABC
        Route::put('{client}/classification', [ClientController::class, 'setClassification'])->name('clients.classification');
        // Contatos (financeiro e emergência)
        Route::put('{client}/financial-contact', [ClientController::class, 'setFinancialContact'])->name('clients.financial-contact');
        Route::put('{client}/emergency-contact', [ClientController::class, 'setEmergencyContact'])->name('clients.emergency-contact');
        // Revisões periódicas
        Route::get('{client}/reviews', [ClientController::class, 'reviews'])->name('clients.reviews.index');
        Route::post('{client}/reviews', [ClientController::class, 'createReview'])->name('clients.reviews.store');
        // Timeline de eventos
        Route::get('{client}/events', [ClientController::class, 'events'])->name('clients.events');
        Route::post('{client}/events', [ClientController::class, 'logEvent'])->name('clients.events.store');
        // Indicações
        Route::get('{client}/referrals', [ClientController::class, 'referrals'])->name('clients.referrals.index');
        Route::post('{client}/referrals', [ClientController::class, 'createReferral'])->name('clients.referrals.store');
        // Completude do cadastro
        Route::get('{client}/completeness', [ClientController::class, 'completeness'])->name('clients.completeness');
    });

    /*
    |--------------------------------------------------------------------------
    | Revisões de Clientes
    |--------------------------------------------------------------------------
    */
    Route::prefix('reviews')->group(function () {
        Route::get('pending', [ClientController::class, 'pendingReviews'])->name('reviews.pending');
        Route::get('upcoming', [ClientController::class, 'upcomingReviews'])->name('reviews.upcoming');
        Route::get('statistics', [ClientController::class, 'reviewStatistics'])->name('reviews.statistics');
        Route::get('nps', [ClientController::class, 'nps'])->name('reviews.nps');
    });

    /*
    |--------------------------------------------------------------------------
    | Indicações (Programa de Referral)
    |--------------------------------------------------------------------------
    */
    Route::prefix('referrals')->group(function () {
        Route::get('/', [ClientController::class, 'allReferrals'])->name('referrals.index');
        Route::get('pending', [ClientController::class, 'pendingReferrals'])->name('referrals.pending');
        Route::get('top-referrers', [ClientController::class, 'topReferrers'])->name('referrals.top-referrers');
        Route::get('statistics', [ClientController::class, 'referralStatistics'])->name('referrals.statistics');
        Route::put('{referral}/contacted', [ClientController::class, 'markReferralContacted'])->name('referrals.contacted');
        Route::put('{referral}/converted', [ClientController::class, 'markReferralConverted'])->name('referrals.converted');
        Route::put('{referral}/lost', [ClientController::class, 'markReferralLost'])->name('referrals.lost');
    });

    /*
    |--------------------------------------------------------------------------
    | Pipeline e Deals
    |--------------------------------------------------------------------------
    */
    Route::prefix('pipeline')->group(function () {
        Route::get('stages', [PipelineController::class, 'stages'])->name('pipeline.stages');
        Route::get('board', [PipelineController::class, 'board'])->name('pipeline.board');
        Route::get('metrics', [PipelineController::class, 'metrics'])->name('pipeline.metrics');
        Route::get('conversion-rates', [PipelineController::class, 'conversionRates'])->name('pipeline.conversion');
        Route::get('stage-duration', [PipelineController::class, 'stageDuration'])->name('pipeline.duration');
        Route::get('forecast', [PipelineController::class, 'forecast'])->name('pipeline.forecast');
        Route::get('stages/{stage}/deals', [PipelineController::class, 'stageDeals'])->name('pipeline.stage.deals');
        
        // Admin routes
        Route::post('stages', [PipelineController::class, 'createStage'])->name('pipeline.stages.store');
        Route::put('stages/{stage}', [PipelineController::class, 'updateStage'])->name('pipeline.stages.update');
        Route::post('stages/reorder', [PipelineController::class, 'reorderStages'])->name('pipeline.stages.reorder');
    });

    Route::prefix('deals')->group(function () {
        Route::get('/', [DealController::class, 'index'])->name('deals.index');
        Route::post('/', [DealController::class, 'store'])->name('deals.store');
        Route::get('{deal}', [DealController::class, 'show'])->name('deals.show');
        Route::put('{deal}', [DealController::class, 'update'])->name('deals.update');
        Route::delete('{deal}', [DealController::class, 'destroy'])->name('deals.destroy');
        Route::post('{deal}/next-stage', [DealController::class, 'moveToNextStage'])->name('deals.next-stage');
        Route::post('{deal}/move-stage', [DealController::class, 'moveToStage'])->name('deals.move-stage');
        Route::post('{deal}/won', [DealController::class, 'markAsWon'])->name('deals.won');
        Route::post('{deal}/lost', [DealController::class, 'markAsLost'])->name('deals.lost');
    });

    /*
    |--------------------------------------------------------------------------
    | Contratos
    |--------------------------------------------------------------------------
    */
    Route::prefix('contracts')->group(function () {
        Route::get('/', [ContractController::class, 'index'])->name('contracts.index');
        Route::post('/', [ContractController::class, 'store'])->name('contracts.store');
        Route::get('expiring-soon', [ContractController::class, 'expiringSoon'])->name('contracts.expiring');
        Route::get('{contract}', [ContractController::class, 'show'])->name('contracts.show');
        Route::put('{contract}', [ContractController::class, 'update'])->name('contracts.update');
        Route::delete('{contract}', [ContractController::class, 'destroy'])->name('contracts.destroy');
        Route::post('{contract}/sign', [ContractController::class, 'sign'])->name('contracts.sign');
        Route::post('{contract}/activate', [ContractController::class, 'activate'])->name('contracts.activate');
        Route::post('{contract}/close', [ContractController::class, 'close'])->name('contracts.close');
        Route::get('{contract}/signature-link', [ContractController::class, 'generateSignatureLink'])->name('contracts.signature-link');
    });

    /*
    |--------------------------------------------------------------------------
    | Tarefas
    |--------------------------------------------------------------------------
    */
    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('tasks.index');
        Route::post('/', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('my-tasks', [TaskController::class, 'myTasks'])->name('tasks.my');
        Route::get('overdue', [TaskController::class, 'overdue'])->name('tasks.overdue');
        Route::get('{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::put('{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
        Route::post('{task}/cancel', [TaskController::class, 'cancel'])->name('tasks.cancel');
        Route::post('{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign');
        Route::post('{task}/unassign', [TaskController::class, 'unassign'])->name('tasks.unassign');
    });

    /*
    |--------------------------------------------------------------------------
    | Interações
    |--------------------------------------------------------------------------
    */
    Route::prefix('interactions')->group(function () {
        Route::get('/', [InteractionController::class, 'index'])->name('interactions.index');
        Route::post('/', [InteractionController::class, 'store'])->name('interactions.store');
        Route::get('statistics', [InteractionController::class, 'statistics'])->name('interactions.statistics');
        Route::get('lead/{leadId}', [InteractionController::class, 'forLead'])->name('interactions.lead');
        Route::get('lead/{leadId}/timeline', [InteractionController::class, 'timeline'])->name('interactions.timeline');
        Route::get('{interaction}', [InteractionController::class, 'show'])->name('interactions.show');
        Route::put('{interaction}', [InteractionController::class, 'update'])->name('interactions.update');
        Route::delete('{interaction}', [InteractionController::class, 'destroy'])->name('interactions.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Relatórios
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->group(function () {
        Route::get('dashboard', [ReportController::class, 'dashboard'])->name('reports.dashboard');
        Route::get('conversion', [ReportController::class, 'conversion'])->name('reports.conversion');
        Route::get('lead-sources', [ReportController::class, 'leadSources'])->name('reports.lead-sources');
        Route::get('loss-reasons', [ReportController::class, 'lossReasons'])->name('reports.loss-reasons');
        Route::get('ticket-medio', [ReportController::class, 'ticketMedio'])->name('reports.ticket-medio');
        Route::get('response-time', [ReportController::class, 'responseTime'])->name('reports.response-time');
        Route::get('sales-performance', [ReportController::class, 'salesPerformance'])->name('reports.sales-performance');
        Route::get('contracts', [ReportController::class, 'contracts'])->name('reports.contracts');
        Route::get('clients-by-city', [ReportController::class, 'clientsByCity'])->name('reports.clients-city');
        Route::get('service-types', [ReportController::class, 'serviceTypes'])->name('reports.service-types');
        Route::post('export', [ReportController::class, 'export'])
            ->middleware('throttle:exports')
            ->name('reports.export');
    });
});
