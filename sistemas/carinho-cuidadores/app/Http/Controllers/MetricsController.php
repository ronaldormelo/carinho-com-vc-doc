<?php

namespace App\Http\Controllers;

use App\Services\MetricsService;
use App\Services\WorkloadService;
use App\Models\Caregiver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para métricas e indicadores operacionais.
 */
class MetricsController extends Controller
{
    public function __construct(
        private MetricsService $metricsService,
        private WorkloadService $workloadService
    ) {}

    /**
     * Dashboard completo de indicadores.
     */
    public function dashboard(): JsonResponse
    {
        $dashboard = $this->metricsService->getDashboard();

        return $this->success($dashboard);
    }

    /**
     * Visão geral do banco de cuidadores.
     */
    public function overview(): JsonResponse
    {
        $overview = $this->metricsService->getOverview();

        return $this->success($overview);
    }

    /**
     * Métricas de ativação.
     */
    public function activation(Request $request): JsonResponse
    {
        $days = min((int) $request->get('days', 30), 365);
        $metrics = $this->metricsService->getActivationMetrics($days);

        return $this->success($metrics);
    }

    /**
     * Métricas de ocupação.
     */
    public function occupancy(): JsonResponse
    {
        $metrics = $this->metricsService->getOccupancyMetrics();

        return $this->success($metrics);
    }

    /**
     * Métricas de qualidade.
     */
    public function quality(Request $request): JsonResponse
    {
        $days = min((int) $request->get('days', 30), 365);
        $metrics = $this->metricsService->getQualityMetrics($days);

        return $this->success($metrics);
    }

    /**
     * Alertas operacionais ativos.
     */
    public function alerts(): JsonResponse
    {
        $alerts = $this->metricsService->getAlerts();

        return $this->success($alerts);
    }

    /**
     * Métricas por cidade.
     */
    public function byCity(): JsonResponse
    {
        $metrics = $this->metricsService->getMetricsByCity();

        return $this->success(['cities' => $metrics]);
    }

    /**
     * Métricas por tipo de cuidado.
     */
    public function byCareType(): JsonResponse
    {
        $metrics = $this->metricsService->getMetricsByCareType();

        return $this->success(['care_types' => $metrics]);
    }

    /**
     * Cuidadores sobrecarregados.
     */
    public function overloaded(): JsonResponse
    {
        $caregivers = $this->workloadService->getOverloadedCaregivers();

        return $this->success(['caregivers' => $caregivers]);
    }

    /**
     * Cuidadores disponíveis com capacidade.
     */
    public function available(Request $request): JsonResponse
    {
        $requiredHours = (float) $request->get('required_hours', 8);
        $city = $request->get('city');
        $careType = $request->get('care_type');
        $dayOfWeek = $request->filled('day_of_week') ? (int) $request->get('day_of_week') : null;

        $caregivers = $this->workloadService->getAvailableCaregivers(
            $requiredHours,
            $city,
            $careType,
            $dayOfWeek
        );

        return $this->success([
            'filters' => [
                'required_hours' => $requiredHours,
                'city' => $city,
                'care_type' => $careType,
                'day_of_week' => $dayOfWeek,
            ],
            'count' => count($caregivers),
            'caregivers' => $caregivers,
        ]);
    }

    /**
     * Resumo de carga de trabalho de um cuidador.
     */
    public function workloadSummary(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador não encontrado', 404);
        }

        $weeks = min((int) $request->get('weeks', 4), 12);
        $summary = $this->workloadService->getWorkloadSummary($caregiver, $weeks);

        return $this->success($summary);
    }
}
