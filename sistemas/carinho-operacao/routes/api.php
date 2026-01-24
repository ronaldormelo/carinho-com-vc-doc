<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\EmergencyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BackupCaregiverController;

/*
|--------------------------------------------------------------------------
| API Routes - Carinho Operacao
|--------------------------------------------------------------------------
|
| Rotas da API para o sistema de Operacao.
| Subdominio: operacao.carinho.com.vc
|
*/

// Health Check (publico)
Route::get('/health', [HealthController::class, 'health']);
Route::get('/status', [HealthController::class, 'status']);
Route::get('/info', [HealthController::class, 'info']);

// Webhooks (autenticados por assinatura)
Route::prefix('webhooks')->group(function () {
    Route::post('/whatsapp', [WebhookController::class, 'whatsapp']);
    Route::post('/atendimento', [WebhookController::class, 'atendimento']);
    Route::post('/cuidadores', [WebhookController::class, 'cuidadores']);
    Route::get('/health', [WebhookController::class, 'health']);
});

// Rotas protegidas por token interno
Route::middleware('internal.token')->group(function () {

    // Solicitacoes de Servico
    Route::prefix('service-requests')->group(function () {
        Route::get('/', [ServiceRequestController::class, 'index']);
        Route::get('/open', [ServiceRequestController::class, 'open']);
        Route::get('/urgent', [ServiceRequestController::class, 'urgent']);
        Route::get('/stats', [ServiceRequestController::class, 'stats']);
        Route::post('/', [ServiceRequestController::class, 'store']);
        Route::post('/import', [ServiceRequestController::class, 'import']);
        Route::get('/{id}', [ServiceRequestController::class, 'show']);
        Route::post('/{id}/process', [ServiceRequestController::class, 'process']);
        Route::post('/{id}/activate', [ServiceRequestController::class, 'activate']);
        Route::post('/{id}/complete', [ServiceRequestController::class, 'complete']);
        Route::post('/{id}/cancel', [ServiceRequestController::class, 'cancel']);
    });

    // Agendamentos
    Route::prefix('schedules')->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::get('/today', [ScheduleController::class, 'today']);
        Route::get('/upcoming', [ScheduleController::class, 'upcoming']);
        Route::post('/', [ScheduleController::class, 'store']);
        Route::post('/check-availability', [ScheduleController::class, 'checkAvailability']);
        Route::get('/{id}', [ScheduleController::class, 'show']);
        Route::post('/{id}/cancel', [ScheduleController::class, 'cancel']);
        Route::get('/{id}/cancellation-policy', [ScheduleController::class, 'cancellationPolicy']);
        Route::get('/caregiver/{caregiverId}/occupancy', [ScheduleController::class, 'occupancy']);
    });

    // Check-in/Check-out
    Route::prefix('checkin')->group(function () {
        Route::post('/schedule/{scheduleId}/in', [CheckinController::class, 'checkin']);
        Route::post('/schedule/{scheduleId}/out', [CheckinController::class, 'checkout']);
        Route::post('/schedule/{scheduleId}/activities', [CheckinController::class, 'logActivities']);
        Route::get('/schedule/{scheduleId}/logs', [CheckinController::class, 'serviceLogs']);
        Route::get('/delays', [CheckinController::class, 'checkDelays']);
        Route::post('/validate-location', [CheckinController::class, 'validateLocation']);
    });

    // Checklists
    Route::prefix('checklists')->group(function () {
        Route::get('/templates', [CheckinController::class, 'checklistTemplates']);
        Route::get('/service-request/{serviceRequestId}/start', [CheckinController::class, 'startChecklist']);
        Route::get('/service-request/{serviceRequestId}/end', [CheckinController::class, 'endChecklist']);
        Route::patch('/entry/{entryId}', [CheckinController::class, 'updateChecklistItem']);
        Route::patch('/{checklistId}/batch', [CheckinController::class, 'updateChecklistBatch']);
    });

    // Alocacoes
    Route::prefix('assignments')->group(function () {
        Route::get('/', [AssignmentController::class, 'index']);
        Route::get('/{id}', [AssignmentController::class, 'show']);
        Route::get('/service-request/{serviceRequestId}/candidates', [AssignmentController::class, 'findCandidates']);
        Route::post('/service-request/{serviceRequestId}/assign', [AssignmentController::class, 'assign']);
        Route::post('/{id}/confirm', [AssignmentController::class, 'confirm']);
        Route::post('/{id}/substitute', [AssignmentController::class, 'substitute']);
        Route::get('/{id}/substitutes', [AssignmentController::class, 'findSubstitutes']);
        Route::get('/{id}/substitution-history', [AssignmentController::class, 'substitutionHistory']);
        Route::post('/check-compatibility', [AssignmentController::class, 'checkCompatibility']);
        Route::get('/caregiver/{caregiverId}/substitution-stats', [AssignmentController::class, 'caregiverSubstitutionStats']);
    });

    // Emergencias
    Route::prefix('emergencies')->group(function () {
        Route::get('/', [EmergencyController::class, 'index']);
        Route::get('/pending', [EmergencyController::class, 'pending']);
        Route::get('/critical', [EmergencyController::class, 'critical']);
        Route::get('/types', [EmergencyController::class, 'types']);
        Route::get('/stats', [EmergencyController::class, 'stats']);
        Route::post('/', [EmergencyController::class, 'store']);
        Route::get('/{id}', [EmergencyController::class, 'show']);
        Route::post('/{id}/resolve', [EmergencyController::class, 'resolve']);
        Route::post('/{id}/escalate', [EmergencyController::class, 'escalate']);
    });

    // Notificacoes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/pending', [NotificationController::class, 'pending']);
        Route::get('/types', [NotificationController::class, 'types']);
        Route::get('/stats', [NotificationController::class, 'stats']);
        Route::get('/client/{clientId}/history', [NotificationController::class, 'clientHistory']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::post('/{id}/retry', [NotificationController::class, 'retry']);
    });

    // Relatórios Operacionais
    Route::prefix('reports')->group(function () {
        Route::get('/daily', [ReportController::class, 'daily']);
        Route::get('/weekly', [ReportController::class, 'weekly']);
        Route::get('/monthly', [ReportController::class, 'monthly']);
        Route::get('/exceptions', [ReportController::class, 'exceptions']);
    });

    // SLA e Métricas
    Route::prefix('sla')->group(function () {
        Route::get('/dashboard', [ReportController::class, 'slaDashboard']);
        Route::get('/alerts', [ReportController::class, 'slaAlerts']);
        Route::get('/alerts/critical', [ReportController::class, 'slaCriticalAlerts']);
        Route::get('/realtime', [ReportController::class, 'slaRealtime']);
        Route::post('/alerts/{alertId}/acknowledge', [ReportController::class, 'acknowledgeSlaAlert']);
    });

    // Auditoria
    Route::prefix('audit')->group(function () {
        Route::get('/stats', [ReportController::class, 'auditStats']);
        Route::get('/history', [ReportController::class, 'auditHistory']);
    });

    // Exceções Operacionais
    Route::prefix('exceptions')->group(function () {
        Route::get('/pending', [ReportController::class, 'pendingExceptions']);
        Route::post('/{exceptionId}/approve', [ReportController::class, 'approveException']);
        Route::post('/{exceptionId}/reject', [ReportController::class, 'rejectException']);
    });

    // Banco de Cuidadores Backup
    Route::prefix('backup-caregivers')->group(function () {
        Route::get('/', [BackupCaregiverController::class, 'index']);
        Route::post('/', [BackupCaregiverController::class, 'store']);
        Route::delete('/{caregiverId}', [BackupCaregiverController::class, 'destroy']);
        Route::patch('/{caregiverId}/availability', [BackupCaregiverController::class, 'updateAvailability']);
        Route::patch('/{caregiverId}/priority', [BackupCaregiverController::class, 'updatePriority']);
        Route::get('/available', [BackupCaregiverController::class, 'findAvailable']);
        Route::get('/find-best', [BackupCaregiverController::class, 'findBestWithExpansion']);
        Route::get('/stats', [BackupCaregiverController::class, 'stats']);
        Route::get('/regions', [BackupCaregiverController::class, 'regions']);
        Route::get('/usage-history', [BackupCaregiverController::class, 'usageHistory']);
        Route::post('/sync', [BackupCaregiverController::class, 'sync']);
    });

});
