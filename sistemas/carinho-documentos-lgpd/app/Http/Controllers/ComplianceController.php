<?php

namespace App\Http\Controllers;

use App\Services\ComplianceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Controller para relatorios de conformidade LGPD.
 *
 * Fornece endpoints para:
 * - Dashboard de conformidade
 * - Relatorio completo de compliance
 * - Indicadores de risco
 * - Exportacao de relatorios
 */
class ComplianceController extends Controller
{
    public function __construct(
        private ComplianceReportService $complianceService
    ) {}

    /**
     * Dashboard de conformidade.
     *
     * Retorna visao resumida do estado atual de conformidade LGPD.
     */
    public function dashboard(): JsonResponse
    {
        $dashboard = $this->complianceService->generateDashboard();

        return $this->success($dashboard);
    }

    /**
     * Relatorio completo de conformidade.
     *
     * Retorna relatorio detalhado com todas as metricas de compliance.
     */
    public function report(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : now()->startOfMonth();

        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : now();

        $report = $this->complianceService->generateComplianceReport($startDate, $endDate);

        return $this->success($report);
    }

    /**
     * Score de conformidade atual.
     *
     * Retorna apenas o score de compliance com breakdown.
     */
    public function score(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|string|in:day,week,month,quarter,year',
        ]);

        $period = $validated['period'] ?? 'month';

        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $report = $this->complianceService->generateComplianceReport($startDate, now());

        return $this->success([
            'period' => $period,
            'start_date' => $startDate->toIso8601String(),
            'score' => $report['compliance_score'],
        ]);
    }

    /**
     * Indicadores de risco ativos.
     *
     * Retorna apenas os indicadores de risco e recomendacoes.
     */
    public function risks(): JsonResponse
    {
        $report = $this->complianceService->generateComplianceReport(now()->startOfMonth(), now());

        return $this->success([
            'risk_indicators' => $report['risk_indicators'],
            'recommendations' => $report['recommendations'],
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Metricas LGPD detalhadas.
     *
     * Retorna metricas especificas de solicitacoes LGPD.
     */
    public function lgpdMetrics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : now()->startOfMonth();

        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : now();

        $report = $this->complianceService->generateComplianceReport($startDate, $endDate);

        return $this->success([
            'period' => [
                'start' => $startDate->toIso8601String(),
                'end' => $endDate->toIso8601String(),
            ],
            'lgpd_requests' => $report['lgpd_requests'],
            'consents' => $report['consents'],
        ]);
    }

    /**
     * Status das politicas de retencao.
     *
     * Retorna status atual das politicas de retencao por tipo de documento.
     */
    public function retentionStatus(): JsonResponse
    {
        $report = $this->complianceService->generateComplianceReport(now()->startOfMonth(), now());

        return $this->success([
            'retention_policies' => $report['retention_status'],
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Resumo de auditoria.
     *
     * Retorna metricas de auditoria e logs de acesso.
     */
    public function auditSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : now()->startOfMonth();

        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : now();

        $report = $this->complianceService->generateComplianceReport($startDate, $endDate);

        return $this->success([
            'period' => [
                'start' => $startDate->toIso8601String(),
                'end' => $endDate->toIso8601String(),
            ],
            'access_audit' => $report['access_audit'],
            'documents' => $report['documents'],
        ]);
    }
}
