<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\PipelineStage;
use App\Models\Domain\DomainDealStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PipelineService
{
    /**
     * Obtém visão do board (Kanban)
     */
    public function getBoard(array $filters = []): array
    {
        $stages = PipelineStage::active()->ordered()->get();

        $board = [];

        foreach ($stages as $stage) {
            $dealsQuery = Deal::with(['lead', 'proposals'])
                ->where('stage_id', $stage->id)
                ->where('status_id', DomainDealStatus::OPEN);

            // Aplicar filtros
            if (isset($filters['min_value'])) {
                $dealsQuery->where('value_estimated', '>=', $filters['min_value']);
            }

            if (isset($filters['lead_id'])) {
                $dealsQuery->where('lead_id', $filters['lead_id']);
            }

            if (isset($filters['start_date']) && isset($filters['end_date'])) {
                $dealsQuery->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
            }

            $deals = $dealsQuery->orderBy('created_at', 'desc')->get();

            $board[] = [
                'stage' => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'order' => $stage->stage_order,
                ],
                'deals' => $deals,
                'count' => $deals->count(),
                'total_value' => $deals->sum('value_estimated'),
            ];
        }

        return $board;
    }

    /**
     * Obtém métricas do pipeline
     */
    public function getMetrics($startDate, $endDate): array
    {
        // Total de deals no período
        $totalDeals = Deal::whereBetween('created_at', [$startDate, $endDate])->count();
        
        // Deals abertos atualmente
        $openDeals = Deal::open()->count();
        $openValue = Deal::open()->sum('value_estimated');

        // Deals ganhos no período
        $wonDeals = Deal::won()
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();
        $wonValue = Deal::won()
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('value_estimated');

        // Deals perdidos no período
        $lostDeals = Deal::lost()
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        // Taxa de conversão
        $closedDeals = $wonDeals + $lostDeals;
        $conversionRate = $closedDeals > 0 ? round(($wonDeals / $closedDeals) * 100, 2) : 0;

        // Ticket médio
        $avgTicket = $wonDeals > 0 ? round($wonValue / $wonDeals, 2) : 0;

        // Tempo médio no pipeline (deals ganhos)
        $avgCycleTime = Deal::won()
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
            ->value('avg_days');

        // Valor por estágio
        $valueByStage = PipelineStage::active()
            ->ordered()
            ->get()
            ->map(function ($stage) {
                return [
                    'stage' => $stage->name,
                    'count' => $stage->getDealsCount(),
                    'value' => $stage->getDealsValue(),
                ];
            });

        return [
            'total_deals' => $totalDeals,
            'open_deals' => $openDeals,
            'open_value' => round($openValue, 2),
            'won_deals' => $wonDeals,
            'won_value' => round($wonValue, 2),
            'lost_deals' => $lostDeals,
            'conversion_rate' => $conversionRate,
            'avg_ticket' => $avgTicket,
            'avg_cycle_days' => round($avgCycleTime ?? 0, 1),
            'by_stage' => $valueByStage,
        ];
    }

    /**
     * Obtém taxas de conversão por estágio
     */
    public function getConversionRates($startDate, $endDate): array
    {
        $stages = PipelineStage::active()->ordered()->get();
        $rates = [];

        foreach ($stages as $index => $stage) {
            $dealsEntered = Deal::where('stage_id', $stage->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Deals que avançaram para o próximo estágio
            $nextStage = $stages->get($index + 1);
            $dealsAdvanced = 0;

            if ($nextStage) {
                // Simplificação: contar deals que chegaram ao próximo estágio
                $dealsAdvanced = Deal::where('stage_id', $nextStage->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();
            } else {
                // Último estágio: deals ganhos
                $dealsAdvanced = Deal::where('stage_id', $stage->id)
                    ->won()
                    ->whereBetween('updated_at', [$startDate, $endDate])
                    ->count();
            }

            $conversionRate = $dealsEntered > 0 
                ? round(($dealsAdvanced / $dealsEntered) * 100, 2) 
                : 0;

            $rates[] = [
                'stage' => $stage->name,
                'stage_order' => $stage->stage_order,
                'deals_entered' => $dealsEntered,
                'deals_advanced' => $dealsAdvanced,
                'conversion_rate' => $conversionRate,
            ];
        }

        return $rates;
    }

    /**
     * Obtém tempo médio em cada estágio
     */
    public function getStageDuration($startDate, $endDate): array
    {
        // Simplificação: usa o tempo de atualização como proxy
        $stages = PipelineStage::active()->ordered()->get();
        $durations = [];

        foreach ($stages as $stage) {
            $avgDays = Deal::where('stage_id', $stage->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('AVG(DATEDIFF(NOW(), updated_at)) as avg_days')
                ->value('avg_days');

            $durations[] = [
                'stage' => $stage->name,
                'avg_days' => round($avgDays ?? 0, 1),
            ];
        }

        return $durations;
    }

    /**
     * Obtém forecast de vendas
     */
    public function getForecast(): array
    {
        $stages = PipelineStage::active()->ordered()->get();
        
        // Probabilidades de conversão por estágio (exemplo)
        $probabilities = [
            1 => 0.10, // Novo Lead: 10%
            2 => 0.25, // Primeiro Contato: 25%
            3 => 0.40, // Entendimento: 40%
            4 => 0.60, // Proposta Enviada: 60%
            5 => 0.80, // Negociação: 80%
            6 => 0.95, // Fechamento: 95%
        ];

        $forecast = [];
        $totalWeighted = 0;
        $totalUnweighted = 0;

        foreach ($stages as $stage) {
            $deals = Deal::open()
                ->where('stage_id', $stage->id)
                ->get();

            $stageValue = $deals->sum('value_estimated');
            $probability = $probabilities[$stage->id] ?? 0.50;
            $weightedValue = $stageValue * $probability;

            $totalUnweighted += $stageValue;
            $totalWeighted += $weightedValue;

            $forecast[] = [
                'stage' => $stage->name,
                'deals_count' => $deals->count(),
                'total_value' => round($stageValue, 2),
                'probability' => $probability * 100,
                'weighted_value' => round($weightedValue, 2),
            ];
        }

        return [
            'by_stage' => $forecast,
            'total_pipeline' => round($totalUnweighted, 2),
            'weighted_forecast' => round($totalWeighted, 2),
        ];
    }

    /**
     * Reordena estágios do pipeline
     */
    public function reorderStages(array $stages): void
    {
        DB::transaction(function () use ($stages) {
            foreach ($stages as $stageData) {
                PipelineStage::where('id', $stageData['id'])
                    ->update(['stage_order' => $stageData['order']]);
            }
        });

        PipelineStage::clearCache();
    }
}
