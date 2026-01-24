<?php

namespace App\Http\Controllers;

use App\Services\RoiReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para relatórios de ROI.
 */
class RoiReportController extends Controller
{
    public function __construct(
        private RoiReportService $service
    ) {}

    /**
     * Obtém relatório de ROI consolidado.
     */
    public function consolidated(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'average_ticket' => 'nullable|numeric|min:0',
            'recurrence_months' => 'nullable|integer|min:1',
        ]);

        $report = $this->service->getConsolidatedReport(
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('average_ticket'),
            $request->input('recurrence_months')
        );

        return $this->success($report, 'Relatório de ROI carregado');
    }

    /**
     * Obtém comparativo entre períodos.
     */
    public function comparison(Request $request): JsonResponse
    {
        $request->validate([
            'current_start' => 'required|date',
            'current_end' => 'required|date|after_or_equal:current_start',
            'previous_start' => 'required|date',
            'previous_end' => 'required|date|after_or_equal:previous_start',
        ]);

        $comparison = $this->service->getComparison(
            $request->input('current_start'),
            $request->input('current_end'),
            $request->input('previous_start'),
            $request->input('previous_end')
        );

        return $this->success($comparison, 'Comparativo carregado');
    }

    /**
     * Obtém relatório do mês atual.
     */
    public function currentMonth(Request $request): JsonResponse
    {
        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->toDateString();

        $report = $this->service->getConsolidatedReport(
            $startDate,
            $endDate,
            $request->input('average_ticket'),
            $request->input('recurrence_months')
        );

        return $this->success($report, 'Relatório do mês atual carregado');
    }

    /**
     * Obtém relatório mensal com comparativo ao mês anterior.
     */
    public function monthly(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $currentStart = "{$year}-{$month}-01";
        $currentEnd = date('Y-m-t', strtotime($currentStart));

        // Mês anterior
        $previousDate = date('Y-m-d', strtotime($currentStart . ' -1 month'));
        $previousStart = date('Y-m-01', strtotime($previousDate));
        $previousEnd = date('Y-m-t', strtotime($previousDate));

        $comparison = $this->service->getComparison(
            $currentStart,
            $currentEnd,
            $previousStart,
            $previousEnd
        );

        return $this->success($comparison, 'Relatório mensal carregado');
    }

    /**
     * Obtém relatório trimestral.
     */
    public function quarterly(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020',
            'quarter' => 'nullable|integer|min:1|max:4',
        ]);

        $year = $request->input('year', now()->year);
        $quarter = $request->input('quarter', ceil(now()->month / 3));

        $startMonth = (($quarter - 1) * 3) + 1;
        $endMonth = $startMonth + 2;

        $startDate = sprintf('%d-%02d-01', $year, $startMonth);
        $endDate = date('Y-m-t', strtotime(sprintf('%d-%02d-01', $year, $endMonth)));

        $report = $this->service->getConsolidatedReport(
            $startDate,
            $endDate,
            $request->input('average_ticket'),
            $request->input('recurrence_months')
        );

        return $this->success([
            'quarter' => $quarter,
            'year' => $year,
            'report' => $report,
        ], 'Relatório trimestral carregado');
    }
}
