<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Services\SlaService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para relatórios operacionais consolidados.
 */
class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected SlaService $slaService,
        protected AuditService $auditService
    ) {}

    /**
     * Relatório diário.
     */
    public function daily(Request $request): JsonResponse
    {
        $date = $request->query('date');

        $report = $this->reportService->getDailyReport($date);

        return $this->success($report);
    }

    /**
     * Relatório semanal.
     */
    public function weekly(Request $request): JsonResponse
    {
        $weekStart = $request->query('week_start');

        $report = $this->reportService->getWeeklyReport($weekStart);

        return $this->success($report);
    }

    /**
     * Relatório mensal.
     */
    public function monthly(Request $request): JsonResponse
    {
        $month = $request->query('month');

        $report = $this->reportService->getMonthlyReport($month);

        return $this->success($report);
    }

    /**
     * Relatório de exceções.
     */
    public function exceptions(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $report = $this->reportService->getExceptionsReport($startDate, $endDate);

        return $this->success($report);
    }

    /**
     * Dashboard de SLA.
     */
    public function slaDashboard(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $dashboard = $this->slaService->getDashboard($startDate, $endDate);

        return $this->success($dashboard);
    }

    /**
     * Alertas de SLA não confirmados.
     */
    public function slaAlerts(): JsonResponse
    {
        $alerts = $this->slaService->getUnacknowledgedAlerts();

        return $this->success([
            'alerts' => $alerts,
            'count' => $alerts->count(),
            'critical_count' => $alerts->where('severity', 'critical')->count(),
        ]);
    }

    /**
     * Alertas críticos de SLA.
     */
    public function slaCriticalAlerts(): JsonResponse
    {
        $alerts = $this->slaService->getCriticalAlerts();

        return $this->success([
            'alerts' => $alerts,
            'count' => $alerts->count(),
        ]);
    }

    /**
     * Confirma alerta de SLA.
     */
    public function acknowledgeSlaAlert(Request $request, int $alertId): JsonResponse
    {
        $alert = \App\Models\SlaAlert::find($alertId);

        if (!$alert) {
            return $this->notFound('Alerta não encontrado.');
        }

        $validated = $request->validate([
            'user_id' => 'required|integer',
        ]);

        $alert = $this->slaService->acknowledgeAlert($alert, $validated['user_id']);

        return $this->success($alert, 'Alerta confirmado.');
    }

    /**
     * Verificação de SLA em tempo real.
     */
    public function slaRealtime(): JsonResponse
    {
        $alerts = $this->slaService->checkRealTimeSla();

        return $this->success([
            'alerts' => $alerts,
            'count' => count($alerts),
            'has_critical' => collect($alerts)->contains('severity', 'critical'),
        ]);
    }

    /**
     * Estatísticas de auditoria.
     */
    public function auditStats(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $stats = $this->auditService->getAuditStats($startDate, $endDate);

        return $this->success($stats);
    }

    /**
     * Histórico de auditoria de uma entidade.
     */
    public function auditHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|string',
            'entity_id' => 'required|integer',
        ]);

        $history = $this->auditService->getEntityHistory(
            $validated['entity_type'],
            $validated['entity_id']
        );

        return $this->success($history);
    }

    /**
     * Lista exceções pendentes.
     */
    public function pendingExceptions(): JsonResponse
    {
        $exceptions = $this->auditService->getPendingExceptions();

        return $this->success([
            'exceptions' => $exceptions,
            'count' => $exceptions->count(),
        ]);
    }

    /**
     * Aprova exceção operacional.
     */
    public function approveException(Request $request, int $exceptionId): JsonResponse
    {
        $exception = \App\Models\OperationalException::find($exceptionId);

        if (!$exception) {
            return $this->notFound('Exceção não encontrada.');
        }

        if (!$exception->isPending()) {
            return $this->error('Exceção já foi processada.');
        }

        $validated = $request->validate([
            'approved_by' => 'required|integer',
            'notes' => 'nullable|string|max:1000',
        ]);

        $exception = $this->auditService->approveException(
            $exception,
            $validated['approved_by'],
            $validated['notes'] ?? null
        );

        return $this->success($exception, 'Exceção aprovada.');
    }

    /**
     * Rejeita exceção operacional.
     */
    public function rejectException(Request $request, int $exceptionId): JsonResponse
    {
        $exception = \App\Models\OperationalException::find($exceptionId);

        if (!$exception) {
            return $this->notFound('Exceção não encontrada.');
        }

        if (!$exception->isPending()) {
            return $this->error('Exceção já foi processada.');
        }

        $validated = $request->validate([
            'rejected_by' => 'required|integer',
            'notes' => 'nullable|string|max:1000',
        ]);

        $exception = $this->auditService->rejectException(
            $exception,
            $validated['rejected_by'],
            $validated['notes'] ?? null
        );

        return $this->success($exception, 'Exceção rejeitada.');
    }
}
