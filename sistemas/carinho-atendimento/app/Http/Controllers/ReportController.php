<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\FunnelService;
use App\Services\SlaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private FunnelService $funnelService,
        private SlaService $slaService,
        private AuditService $auditService
    ) {
    }

    /**
     * Retorna estatísticas do funil de atendimento
     */
    public function funnelStats(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfDay()->toDateTimeString());

        $stats = $this->funnelService->getFunnelStats($startDate, $endDate);

        return response()->json([
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Retorna estatísticas de perdas por motivo
     */
    public function lossStats(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfDay()->toDateTimeString());

        $stats = $this->funnelService->getLossStats($startDate, $endDate);

        return response()->json([
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'losses' => $stats,
        ]);
    }

    /**
     * Retorna estatísticas de SLA
     */
    public function slaStats(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfDay()->toDateTimeString());

        $stats = $this->slaService->getSlaStats($startDate, $endDate);

        return response()->json([
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Retorna ações recentes de um agente
     */
    public function agentActions(Request $request, int $agentId): JsonResponse
    {
        $limit = (int) $request->input('limit', 50);
        $limit = min($limit, 100); // Máximo de 100

        $actions = $this->auditService->getAgentRecentActions($agentId, $limit);

        return response()->json(['actions' => $actions]);
    }

    /**
     * Retorna estatísticas de ações por tipo
     */
    public function actionStats(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfDay()->toDateTimeString());
        $agentId = $request->input('agent_id');

        $stats = $this->auditService->getActionStats($startDate, $endDate, $agentId);

        return response()->json([
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'stats' => $stats,
        ]);
    }
}
