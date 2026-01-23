<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\CaregiverController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReferenceController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema Carinho Cuidadores
|--------------------------------------------------------------------------
|
| Rotas da API RESTful para gestao de cuidadores.
| Subdominio: cuidadores.carinho.com.vc
|
*/

// Health check (publico)
Route::get('/health', [HealthController::class, 'show']);

// Webhooks (com validacao propria)
Route::prefix('webhooks')->group(function () {
    Route::post('/whatsapp/z-api', [WebhookController::class, 'whatsapp']);
    Route::post('/documents', [WebhookController::class, 'documents']);
    Route::post('/operacao', [WebhookController::class, 'operacao']);
});

// Rotas protegidas por token interno
Route::middleware(['internal.token'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Cuidadores
    |--------------------------------------------------------------------------
    */
    Route::prefix('caregivers')->group(function () {
        // CRUD basico
        Route::get('/', [CaregiverController::class, 'index']);
        Route::post('/', [CaregiverController::class, 'store']);
        Route::get('/{id}', [CaregiverController::class, 'show']);
        Route::put('/{id}', [CaregiverController::class, 'update']);

        // Acoes de status
        Route::post('/{id}/activate', [CaregiverController::class, 'activate']);
        Route::post('/{id}/deactivate', [CaregiverController::class, 'deactivate']);
        Route::post('/{id}/block', [CaregiverController::class, 'block']);
        Route::get('/{id}/eligibility', [CaregiverController::class, 'checkEligibility']);

        // Documentos
        Route::get('/{caregiverId}/documents', [DocumentController::class, 'index']);
        Route::post('/{caregiverId}/documents', [DocumentController::class, 'store']);
        Route::get('/{caregiverId}/documents/{documentId}', [DocumentController::class, 'show']);
        Route::post('/{caregiverId}/documents/{documentId}/approve', [DocumentController::class, 'approve']);
        Route::post('/{caregiverId}/documents/{documentId}/reject', [DocumentController::class, 'reject']);

        // Habilidades
        Route::get('/{caregiverId}/skills', [SkillController::class, 'index']);
        Route::post('/{caregiverId}/skills', [SkillController::class, 'store']);
        Route::delete('/{caregiverId}/skills/{skillId}', [SkillController::class, 'destroy']);
        Route::put('/{caregiverId}/skills/sync', [SkillController::class, 'sync']);

        // Disponibilidade
        Route::get('/{caregiverId}/availability', [AvailabilityController::class, 'index']);
        Route::post('/{caregiverId}/availability', [AvailabilityController::class, 'store']);
        Route::put('/{caregiverId}/availability/{availabilityId}', [AvailabilityController::class, 'update']);
        Route::delete('/{caregiverId}/availability/{availabilityId}', [AvailabilityController::class, 'destroy']);
        Route::put('/{caregiverId}/availability/sync', [AvailabilityController::class, 'sync']);

        // Regioes
        Route::get('/{caregiverId}/regions', [RegionController::class, 'index']);
        Route::post('/{caregiverId}/regions', [RegionController::class, 'store']);
        Route::delete('/{caregiverId}/regions/{regionId}', [RegionController::class, 'destroy']);
        Route::put('/{caregiverId}/regions/sync', [RegionController::class, 'sync']);

        // Contratos
        Route::get('/{caregiverId}/contracts', [ContractController::class, 'index']);
        Route::post('/{caregiverId}/contracts', [ContractController::class, 'store']);
        Route::get('/{caregiverId}/contracts/{contractId}', [ContractController::class, 'show']);
        Route::post('/{caregiverId}/contracts/{contractId}/sign', [ContractController::class, 'sign']);
        Route::post('/{caregiverId}/contracts/{contractId}/activate', [ContractController::class, 'activate']);
        Route::post('/{caregiverId}/contracts/{contractId}/close', [ContractController::class, 'close']);
        Route::post('/{caregiverId}/contracts/{contractId}/send', [ContractController::class, 'send']);

        // Avaliacoes
        Route::get('/{caregiverId}/ratings', [RatingController::class, 'index']);
        Route::post('/{caregiverId}/ratings', [RatingController::class, 'store']);
        Route::get('/{caregiverId}/ratings/{ratingId}', [RatingController::class, 'show']);
        Route::get('/{caregiverId}/ratings-summary', [RatingController::class, 'summary']);

        // Ocorrencias
        Route::get('/{caregiverId}/incidents', [IncidentController::class, 'index']);
        Route::post('/{caregiverId}/incidents', [IncidentController::class, 'store']);
        Route::get('/{caregiverId}/incidents/{incidentId}', [IncidentController::class, 'show']);
        Route::put('/{caregiverId}/incidents/{incidentId}', [IncidentController::class, 'update']);
        Route::post('/{caregiverId}/incidents/{incidentId}/resolve', [IncidentController::class, 'resolve']);

        // Treinamentos
        Route::get('/{caregiverId}/trainings', [TrainingController::class, 'index']);
        Route::post('/{caregiverId}/trainings', [TrainingController::class, 'store']);
        Route::post('/{caregiverId}/trainings/{trainingId}/complete', [TrainingController::class, 'complete']);
        Route::delete('/{caregiverId}/trainings/{trainingId}', [TrainingController::class, 'destroy']);

        // Afastamentos (atestados, férias, licenças)
        Route::get('/{caregiverId}/leaves', [LeaveController::class, 'index']);
        Route::post('/{caregiverId}/leaves', [LeaveController::class, 'store']);
        Route::get('/{caregiverId}/leaves/{leaveId}', [LeaveController::class, 'show']);
        Route::put('/{caregiverId}/leaves/{leaveId}', [LeaveController::class, 'update']);
        Route::post('/{caregiverId}/leaves/{leaveId}/approve', [LeaveController::class, 'approve']);
        Route::post('/{caregiverId}/leaves/{leaveId}/reject', [LeaveController::class, 'reject']);
        Route::delete('/{caregiverId}/leaves/{leaveId}', [LeaveController::class, 'destroy']);

        // Alocações/Serviços
        Route::get('/{caregiverId}/assignments', [AssignmentController::class, 'index']);
        Route::post('/{caregiverId}/assignments', [AssignmentController::class, 'store']);
        Route::get('/{caregiverId}/assignments/{assignmentId}', [AssignmentController::class, 'show']);
        Route::put('/{caregiverId}/assignments/{assignmentId}', [AssignmentController::class, 'update']);
        Route::post('/{caregiverId}/assignments/{assignmentId}/complete', [AssignmentController::class, 'complete']);
        Route::post('/{caregiverId}/assignments/{assignmentId}/cancel', [AssignmentController::class, 'cancel']);

        // Carga de trabalho
        Route::get('/{caregiverId}/workload', [MetricsController::class, 'workloadSummary']);
    });

    /*
    |--------------------------------------------------------------------------
    | Busca
    |--------------------------------------------------------------------------
    */
    Route::prefix('search')->group(function () {
        Route::post('/', [SearchController::class, 'search']);
        Route::get('/quick', [SearchController::class, 'quick']);
        Route::get('/filters', [SearchController::class, 'filters']);
        Route::get('/available', [SearchController::class, 'available']);
        Route::get('/stats', [SearchController::class, 'stats']);
    });

    /*
    |--------------------------------------------------------------------------
    | Documentos (admin)
    |--------------------------------------------------------------------------
    */
    Route::prefix('documents')->group(function () {
        Route::get('/pending', [DocumentController::class, 'pending']);
    });

    /*
    |--------------------------------------------------------------------------
    | Avaliacoes (admin)
    |--------------------------------------------------------------------------
    */
    Route::prefix('ratings')->group(function () {
        Route::get('/top-rated', [RatingController::class, 'topRated']);
        Route::get('/needs-attention', [RatingController::class, 'needsAttention']);
    });

    /*
    |--------------------------------------------------------------------------
    | Ocorrencias (admin)
    |--------------------------------------------------------------------------
    */
    Route::prefix('incidents')->group(function () {
        Route::get('/types', [IncidentController::class, 'types']);
        Route::get('/recent', [IncidentController::class, 'recent']);
        Route::get('/stats', [IncidentController::class, 'stats']);
    });

    /*
    |--------------------------------------------------------------------------
    | Dados auxiliares
    |--------------------------------------------------------------------------
    */
    Route::prefix('regions')->group(function () {
        Route::get('/cities', [RegionController::class, 'cities']);
        Route::get('/cities/{city}/neighborhoods', [RegionController::class, 'neighborhoods']);
    });

    Route::prefix('trainings')->group(function () {
        Route::get('/available-courses', [TrainingController::class, 'availableCourses']);
    });

    /*
    |--------------------------------------------------------------------------
    | Métricas e Indicadores Operacionais
    |--------------------------------------------------------------------------
    */
    Route::prefix('metrics')->group(function () {
        Route::get('/dashboard', [MetricsController::class, 'dashboard']);
        Route::get('/overview', [MetricsController::class, 'overview']);
        Route::get('/activation', [MetricsController::class, 'activation']);
        Route::get('/occupancy', [MetricsController::class, 'occupancy']);
        Route::get('/quality', [MetricsController::class, 'quality']);
        Route::get('/alerts', [MetricsController::class, 'alerts']);
        Route::get('/by-city', [MetricsController::class, 'byCity']);
        Route::get('/by-care-type', [MetricsController::class, 'byCareType']);
        Route::get('/overloaded', [MetricsController::class, 'overloaded']);
        Route::get('/available', [MetricsController::class, 'available']);
    });

    /*
    |--------------------------------------------------------------------------
    | Afastamentos (admin)
    |--------------------------------------------------------------------------
    */
    Route::prefix('leaves')->group(function () {
        Route::get('/types', [LeaveController::class, 'types']);
        Route::get('/pending', [LeaveController::class, 'pending']);
        Route::get('/current', [LeaveController::class, 'current']);
    });

    /*
    |--------------------------------------------------------------------------
    | Alocações (admin)
    |--------------------------------------------------------------------------
    */
    Route::prefix('assignments')->group(function () {
        Route::get('/statuses', [AssignmentController::class, 'statuses']);
        Route::get('/history', [AssignmentController::class, 'history']);
    });
});
