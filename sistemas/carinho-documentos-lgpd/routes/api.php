<?php

use App\Http\Controllers\AccessLogController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DataRequestController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\RetentionPolicyController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Sistema: Carinho Documentos e LGPD
| Subdominio: documentos.carinho.com.vc
|
*/

// Health check
Route::get('/health', [HealthController::class, 'check']);
Route::get('/up', [HealthController::class, 'up']);

// Webhooks externos (sem autenticacao de token interno)
Route::prefix('webhooks')->group(function () {
    Route::post('/signature', [WebhookController::class, 'signature']);
    Route::post('/storage', [WebhookController::class, 'storage']);
});

// Rotas internas (autenticacao por token entre sistemas)
Route::middleware('internal.token')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Documentos
    |--------------------------------------------------------------------------
    */
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/', [DocumentController::class, 'store']);
        Route::get('/{id}', [DocumentController::class, 'show']);
        Route::put('/{id}', [DocumentController::class, 'update']);
        Route::delete('/{id}', [DocumentController::class, 'destroy']);

        // Upload e download
        Route::post('/upload', [DocumentController::class, 'upload']);
        Route::get('/{id}/download', [DocumentController::class, 'download']);
        Route::get('/{id}/signed-url', [DocumentController::class, 'signedUrl']);

        // Validacao automatica
        Route::post('/validate', [DocumentController::class, 'validateDocument']);

        // Versoes
        Route::get('/{id}/versions', [DocumentController::class, 'versions']);
        Route::post('/{id}/versions', [DocumentController::class, 'createVersion']);

        // Busca por proprietario
        Route::get('/owner/{ownerType}/{ownerId}', [DocumentController::class, 'byOwner']);
    });

    /*
    |--------------------------------------------------------------------------
    | Templates de Documentos
    |--------------------------------------------------------------------------
    */
    Route::prefix('templates')->group(function () {
        Route::get('/', [DocumentTemplateController::class, 'index']);
        Route::post('/', [DocumentTemplateController::class, 'store']);
        Route::get('/{id}', [DocumentTemplateController::class, 'show']);
        Route::put('/{id}', [DocumentTemplateController::class, 'update']);
        Route::delete('/{id}', [DocumentTemplateController::class, 'destroy']);
        Route::get('/type/{type}', [DocumentTemplateController::class, 'byType']);
        Route::post('/{id}/render', [DocumentTemplateController::class, 'render']);
    });

    /*
    |--------------------------------------------------------------------------
    | Contratos
    |--------------------------------------------------------------------------
    */
    Route::prefix('contracts')->group(function () {
        Route::get('/', [ContractController::class, 'index']);
        Route::post('/', [ContractController::class, 'store']);
        Route::get('/{id}', [ContractController::class, 'show']);
        Route::put('/{id}', [ContractController::class, 'update']);

        // Assinatura
        Route::post('/{id}/sign', [ContractController::class, 'sign']);
        Route::get('/{id}/signature-url', [ContractController::class, 'signatureUrl']);
        Route::get('/{id}/status', [ContractController::class, 'status']);

        // Download
        Route::get('/{id}/download', [ContractController::class, 'download']);
        Route::get('/{id}/pdf', [ContractController::class, 'pdf']);

        // Por tipo de proprietario
        Route::get('/client/{clientId}', [ContractController::class, 'byClient']);
        Route::get('/caregiver/{caregiverId}', [ContractController::class, 'byCaregiver']);
    });

    /*
    |--------------------------------------------------------------------------
    | Assinaturas
    |--------------------------------------------------------------------------
    */
    Route::prefix('signatures')->group(function () {
        Route::get('/', [SignatureController::class, 'index']);
        Route::post('/', [SignatureController::class, 'store']);
        Route::get('/{id}', [SignatureController::class, 'show']);
        Route::get('/document/{documentId}', [SignatureController::class, 'byDocument']);
        Route::get('/verify/{id}', [SignatureController::class, 'verify']);

        // OTP para assinatura
        Route::post('/send-otp', [SignatureController::class, 'sendOtp']);
        Route::post('/verify-otp', [SignatureController::class, 'verifyOtp']);
    });

    /*
    |--------------------------------------------------------------------------
    | Consentimentos LGPD
    |--------------------------------------------------------------------------
    */
    Route::prefix('consents')->group(function () {
        Route::get('/', [ConsentController::class, 'index']);
        Route::post('/', [ConsentController::class, 'store']);
        Route::get('/{id}', [ConsentController::class, 'show']);
        Route::delete('/{id}', [ConsentController::class, 'revoke']);

        // Por titular
        Route::get('/subject/{subjectType}/{subjectId}', [ConsentController::class, 'bySubject']);

        // Verificar consentimento ativo
        Route::get('/check/{subjectType}/{subjectId}/{consentType}', [ConsentController::class, 'check']);

        // Historico
        Route::get('/history/{subjectType}/{subjectId}', [ConsentController::class, 'history']);
    });

    /*
    |--------------------------------------------------------------------------
    | Solicitacoes de Dados LGPD
    |--------------------------------------------------------------------------
    */
    Route::prefix('data-requests')->group(function () {
        Route::get('/', [DataRequestController::class, 'index']);
        Route::post('/', [DataRequestController::class, 'store']);
        Route::get('/{id}', [DataRequestController::class, 'show']);
        Route::put('/{id}', [DataRequestController::class, 'update']);

        // Processar solicitacao
        Route::post('/{id}/process', [DataRequestController::class, 'process']);

        // Exportacao de dados
        Route::post('/export', [DataRequestController::class, 'requestExport']);
        Route::get('/export/{id}/download', [DataRequestController::class, 'downloadExport']);

        // Exclusao de dados
        Route::post('/delete', [DataRequestController::class, 'requestDelete']);
    });

    /*
    |--------------------------------------------------------------------------
    | Logs de Acesso
    |--------------------------------------------------------------------------
    */
    Route::prefix('access-logs')->group(function () {
        Route::get('/', [AccessLogController::class, 'index']);
        Route::get('/document/{documentId}', [AccessLogController::class, 'byDocument']);
        Route::get('/actor/{actorId}', [AccessLogController::class, 'byActor']);
        Route::get('/report', [AccessLogController::class, 'report']);
    });

    /*
    |--------------------------------------------------------------------------
    | Politicas de Retencao
    |--------------------------------------------------------------------------
    */
    Route::prefix('retention-policies')->group(function () {
        Route::get('/', [RetentionPolicyController::class, 'index']);
        Route::post('/', [RetentionPolicyController::class, 'store']);
        Route::get('/{id}', [RetentionPolicyController::class, 'show']);
        Route::put('/{id}', [RetentionPolicyController::class, 'update']);
        Route::delete('/{id}', [RetentionPolicyController::class, 'destroy']);

        // Executar politica
        Route::post('/execute', [RetentionPolicyController::class, 'execute']);
        Route::get('/pending', [RetentionPolicyController::class, 'pending']);
    });

    /*
    |--------------------------------------------------------------------------
    | Conformidade e Relatorios LGPD
    |--------------------------------------------------------------------------
    */
    Route::prefix('compliance')->group(function () {
        // Dashboard de conformidade
        Route::get('/dashboard', [ComplianceController::class, 'dashboard']);

        // Relatorio completo de compliance
        Route::get('/report', [ComplianceController::class, 'report']);

        // Score de conformidade
        Route::get('/score', [ComplianceController::class, 'score']);

        // Indicadores de risco
        Route::get('/risks', [ComplianceController::class, 'risks']);

        // Metricas LGPD
        Route::get('/lgpd-metrics', [ComplianceController::class, 'lgpdMetrics']);

        // Status de retencao
        Route::get('/retention-status', [ComplianceController::class, 'retentionStatus']);

        // Resumo de auditoria
        Route::get('/audit-summary', [ComplianceController::class, 'auditSummary']);
    });
});

// Rotas publicas com token de acesso temporario (para assinatura de contratos)
Route::prefix('public')->group(function () {
    Route::get('/contract/{token}', [ContractController::class, 'showPublic']);
    Route::post('/contract/{token}/sign', [ContractController::class, 'signPublic']);
    Route::get('/terms', [DocumentTemplateController::class, 'publicTerms']);
    Route::get('/privacy', [DocumentTemplateController::class, 'publicPrivacy']);
});
