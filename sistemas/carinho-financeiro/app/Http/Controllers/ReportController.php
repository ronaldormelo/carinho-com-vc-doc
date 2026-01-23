<?php

namespace App\Http\Controllers;

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller de Relatórios Financeiros.
 */
class ReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    /**
     * Gera DRE (Demonstrativo de Resultado).
     */
    public function dre(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $dre = $this->reportService->generateDRE(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json([
            'success' => true,
            'data' => $dre,
        ]);
    }

    /**
     * Gera relatório de Aging.
     */
    public function aging(): JsonResponse
    {
        $aging = $this->reportService->generateAgingReport();

        return response()->json([
            'success' => true,
            'data' => $aging,
        ]);
    }

    /**
     * Gera análise de margem por tipo de serviço.
     */
    public function marginByServiceType(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $margin = $this->reportService->generateMarginByServiceType(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json([
            'success' => true,
            'data' => $margin,
        ]);
    }

    /**
     * Obtém KPIs financeiros.
     */
    public function kpis(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $kpis = $this->reportService->getFinancialKPIs(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json([
            'success' => true,
            'data' => $kpis,
        ]);
    }
}
