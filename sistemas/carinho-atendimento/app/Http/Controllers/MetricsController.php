<?php

namespace App\Http\Controllers;

use App\Services\EscalationService;
use App\Services\FunnelService;
use App\Services\IncidentService;
use App\Services\SatisfactionService;
use App\Services\SlaService;
use App\Services\WorkingHoursService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller de metricas e dashboard operacional.
 *
 * Fornece endpoints para acompanhamento de:
 * - SLA e tempos de resposta
 * - Funil de atendimento
 * - Incidentes e reclamacoes
 * - Satisfacao do cliente (NPS)
 * - Escalonamentos
 */
class MetricsController extends Controller
{
    public function __construct(
        private SlaService $slaService,
        private FunnelService $funnelService,
        private IncidentService $incidentService,
        private SatisfactionService $satisfactionService,
        private EscalationService $escalationService,
        private WorkingHoursService $workingHoursService
    ) {
    }

    /**
     * Dashboard geral com principais metricas.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $period = $request->input('period', 'today');

        return response()->json([
            'period' => $period,
            'sla' => $this->slaService->getMetrics($period),
            'funnel' => $this->funnelService->getFunnelStats($period),
            'incidents' => $this->incidentService->getIncidentStats($period),
            'satisfaction' => $this->satisfactionService->getMetrics($period),
            'escalations' => $this->escalationService->getEscalationStats($period),
            'working_hours' => $this->workingHoursService->getConfiguration(),
        ]);
    }

    /**
     * Metricas detalhadas de SLA.
     */
    public function sla(Request $request): JsonResponse
    {
        $period = $request->input('period', 'today');

        return response()->json([
            'summary' => $this->slaService->getMetrics($period),
            'by_priority' => $this->slaService->getMetricsByPriority($period),
            'violations' => $this->slaService->checkSlaViolations(),
        ]);
    }

    /**
     * Estatisticas do funil de atendimento.
     */
    public function funnel(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');

        return response()->json([
            'stats' => $this->funnelService->getFunnelStats($period),
            'loss_reasons' => $this->funnelService->getLossReasonStats($period),
        ]);
    }

    /**
     * Metricas de incidentes.
     */
    public function incidents(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');

        return response()->json([
            'stats' => $this->incidentService->getIncidentStats($period),
            'pending' => $this->incidentService->getPendingIncidents(),
            'critical' => $this->incidentService->getCriticalPending(),
        ]);
    }

    /**
     * Metricas de satisfacao e NPS.
     */
    public function satisfaction(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');

        return response()->json([
            'metrics' => $this->satisfactionService->getMetrics($period),
            'nps' => $this->satisfactionService->calculateNps($period),
            'low_scores' => $this->satisfactionService->getLowScoreFeedbacks(5),
        ]);
    }

    /**
     * Metricas de escalonamento.
     */
    public function escalations(Request $request): JsonResponse
    {
        $period = $request->input('period', 'week');

        return response()->json([
            'stats' => $this->escalationService->getEscalationStats($period),
        ]);
    }

    /**
     * Status do horario comercial.
     */
    public function workingHours(): JsonResponse
    {
        return response()->json($this->workingHoursService->getConfiguration());
    }

    /**
     * Lista de feriados.
     */
    public function holidays(Request $request): JsonResponse
    {
        $year = $request->input('year', now()->year);

        return response()->json([
            'year' => $year,
            'holidays' => $this->workingHoursService->getHolidaysForYear($year),
        ]);
    }
}
