<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Models\LossReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Dashboard principal com métricas resumidas
     */
    public function dashboard(Request $request)
    {
        $cacheKey = 'reports:dashboard';
        $ttl = config('cache.ttl.dashboard', 300);

        $dashboard = Cache::remember($cacheKey, $ttl, function () {
            return $this->reportService->getDashboardData();
        });

        return $this->successResponse($dashboard);
    }

    /**
     * Relatório de conversão de leads
     */
    public function conversion(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());
        $groupBy = $request->get('group_by', 'day'); // day, week, month

        $cacheKey = "reports:conversion:{$startDate}:{$endDate}:{$groupBy}";
        $ttl = config('cache.ttl.reports', 600);

        $data = Cache::remember($cacheKey, $ttl, function () use ($startDate, $endDate, $groupBy) {
            return $this->reportService->getConversionReport($startDate, $endDate, $groupBy);
        });

        return $this->successResponse($data);
    }

    /**
     * Relatório de origem dos leads
     */
    public function leadSources(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());

        $data = $this->reportService->getLeadSourcesReport($startDate, $endDate);

        return $this->successResponse($data);
    }

    /**
     * Relatório de motivos de perda
     */
    public function lossReasons(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());

        $data = LossReason::getStatistics($startDate, $endDate);

        return $this->successResponse($data);
    }

    /**
     * Relatório de ticket médio
     */
    public function ticketMedio(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());
        $groupBy = $request->get('group_by', 'month');

        $data = $this->reportService->getTicketMedioReport($startDate, $endDate, $groupBy);

        return $this->successResponse($data);
    }

    /**
     * Relatório de tempo médio de resposta
     */
    public function responseTime(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());

        $data = $this->reportService->getResponseTimeReport($startDate, $endDate);

        return $this->successResponse($data);
    }

    /**
     * Relatório de performance por vendedor
     */
    public function salesPerformance(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());

        $data = $this->reportService->getSalesPerformanceReport($startDate, $endDate);

        return $this->successResponse($data);
    }

    /**
     * Relatório de contratos
     */
    public function contracts(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());

        $data = $this->reportService->getContractsReport($startDate, $endDate);

        return $this->successResponse($data);
    }

    /**
     * Relatório de clientes ativos por cidade
     */
    public function clientsByCity(Request $request)
    {
        $data = $this->reportService->getClientsByCityReport();

        return $this->successResponse($data);
    }

    /**
     * Relatório de tipos de serviço mais demandados
     */
    public function serviceTypes(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());

        $data = $this->reportService->getServiceTypesReport($startDate, $endDate);

        return $this->successResponse($data);
    }

    /**
     * Exporta relatório em formato específico
     */
    public function export(Request $request)
    {
        $request->validate([
            'report' => 'required|string|in:leads,clients,deals,contracts',
            'format' => 'required|string|in:xlsx,csv,pdf',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        // Dispatch job para exportação assíncrona
        $job = $this->reportService->exportReport(
            $request->report,
            $request->format,
            $request->start_date,
            $request->end_date,
            auth()->user()
        );

        return $this->successResponse([
            'message' => 'Exportação iniciada. Você receberá um e-mail quando estiver pronta.',
            'job_id' => $job->id ?? null,
        ]);
    }
}
