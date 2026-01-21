<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Client;
use App\Models\Deal;
use App\Models\Contract;
use App\Models\Interaction;
use App\Models\Domain\DomainLeadStatus;
use App\Jobs\ExportReportJob;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        protected LeadService $leadService,
        protected DealService $dealService,
        protected ContractService $contractService,
        protected TaskService $taskService,
        protected InteractionService $interactionService
    ) {}

    /**
     * Obtém dados para o dashboard principal
     */
    public function getDashboardData(): array
    {
        return [
            'leads' => $this->leadService->getStatistics(),
            'deals' => $this->dealService->getStatistics(now()->startOfMonth(), now()),
            'contracts' => $this->contractService->getStatistics(now()->startOfMonth(), now()),
            'tasks' => $this->taskService->getStatistics(),
            'recent_leads' => Lead::with(['status', 'urgency'])
                ->latest()
                ->limit(5)
                ->get(),
            'urgent_leads' => Lead::with(['status', 'urgency'])
                ->urgent()
                ->inPipeline()
                ->limit(5)
                ->get(),
            'expiring_contracts' => Contract::with(['client.lead'])
                ->expiringIn(30)
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Relatório de conversão de leads
     */
    public function getConversionReport($startDate, $endDate, string $groupBy = 'day'): array
    {
        $dateFormat = match($groupBy) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $data = Lead::selectRaw("
            DATE_FORMAT(created_at, '{$dateFormat}') as period,
            COUNT(*) as total,
            SUM(CASE WHEN status_id = ? THEN 1 ELSE 0 END) as converted,
            SUM(CASE WHEN status_id = ? THEN 1 ELSE 0 END) as lost
        ", [DomainLeadStatus::ACTIVE, DomainLeadStatus::LOST])
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('period')
        ->orderBy('period')
        ->get();

        $result = $data->map(function ($item) {
            $completed = $item->converted + $item->lost;
            return [
                'period' => $item->period,
                'total' => $item->total,
                'converted' => $item->converted,
                'lost' => $item->lost,
                'conversion_rate' => $completed > 0 
                    ? round(($item->converted / $completed) * 100, 2) 
                    : 0,
            ];
        });

        return [
            'data' => $result,
            'totals' => [
                'total' => $result->sum('total'),
                'converted' => $result->sum('converted'),
                'lost' => $result->sum('lost'),
            ],
        ];
    }

    /**
     * Relatório de origem dos leads
     */
    public function getLeadSourcesReport($startDate, $endDate): array
    {
        $data = Lead::selectRaw('
            source,
            COUNT(*) as total,
            SUM(CASE WHEN status_id = ? THEN 1 ELSE 0 END) as converted
        ', [DomainLeadStatus::ACTIVE])
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('source')
        ->orderByDesc('total')
        ->get();

        return $data->map(function ($item) {
            return [
                'source' => $item->source,
                'total' => $item->total,
                'converted' => $item->converted,
                'conversion_rate' => $item->total > 0 
                    ? round(($item->converted / $item->total) * 100, 2) 
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Relatório de ticket médio
     */
    public function getTicketMedioReport($startDate, $endDate, string $groupBy = 'month'): array
    {
        $dateFormat = match($groupBy) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $data = Deal::selectRaw("
            DATE_FORMAT(created_at, '{$dateFormat}') as period,
            COUNT(*) as total_deals,
            SUM(value_estimated) as total_value,
            AVG(value_estimated) as avg_value
        ")
        ->won()
        ->whereBetween('updated_at', [$startDate, $endDate])
        ->groupBy('period')
        ->orderBy('period')
        ->get();

        return $data->map(function ($item) {
            return [
                'period' => $item->period,
                'total_deals' => $item->total_deals,
                'total_value' => round($item->total_value, 2),
                'avg_value' => round($item->avg_value, 2),
            ];
        })->toArray();
    }

    /**
     * Relatório de tempo médio de resposta
     */
    public function getResponseTimeReport($startDate, $endDate): array
    {
        $avgHours = $this->interactionService->getAverageFirstResponseTime($startDate, $endDate);

        // Por canal
        $byChannel = DB::select("
            SELECT 
                c.code as channel,
                AVG(TIMESTAMPDIFF(HOUR, l.created_at, i.occurred_at)) as avg_hours
            FROM leads l
            INNER JOIN interactions i ON l.id = i.lead_id
            INNER JOIN domain_interaction_channel c ON i.channel_id = c.id
            WHERE l.created_at BETWEEN ? AND ?
            AND i.occurred_at = (
                SELECT MIN(i2.occurred_at) 
                FROM interactions i2 
                WHERE i2.lead_id = l.id
            )
            GROUP BY c.code
        ", [$startDate, $endDate]);

        return [
            'avg_response_hours' => round($avgHours ?? 0, 2),
            'by_channel' => collect($byChannel)->mapWithKeys(function ($item) {
                return [$item->channel => round($item->avg_hours, 2)];
            })->toArray(),
        ];
    }

    /**
     * Relatório de performance por vendedor
     */
    public function getSalesPerformanceReport($startDate, $endDate): array
    {
        // Simplificação: usa assigned_to das tasks como proxy de vendedor
        $data = DB::select("
            SELECT 
                u.id as user_id,
                u.name as user_name,
                COUNT(DISTINCT t.lead_id) as leads_worked,
                COUNT(DISTINCT CASE WHEN l.status_id = ? THEN l.id END) as leads_converted,
                COALESCE(SUM(d.value_estimated), 0) as total_value
            FROM users u
            LEFT JOIN tasks t ON u.id = t.assigned_to
            LEFT JOIN leads l ON t.lead_id = l.id
            LEFT JOIN deals d ON l.id = d.lead_id AND d.status_id = 2
            WHERE t.due_at BETWEEN ? AND ?
            GROUP BY u.id, u.name
            ORDER BY total_value DESC
        ", [DomainLeadStatus::ACTIVE, $startDate, $endDate]);

        return collect($data)->map(function ($item) {
            return [
                'user_id' => $item->user_id,
                'user_name' => $item->user_name,
                'leads_worked' => $item->leads_worked,
                'leads_converted' => $item->leads_converted,
                'conversion_rate' => $item->leads_worked > 0 
                    ? round(($item->leads_converted / $item->leads_worked) * 100, 2) 
                    : 0,
                'total_value' => round($item->total_value, 2),
            ];
        })->toArray();
    }

    /**
     * Relatório de contratos
     */
    public function getContractsReport($startDate, $endDate): array
    {
        $signed = Contract::whereBetween('signed_at', [$startDate, $endDate])->count();
        $closed = Contract::closed()
            ->whereBetween('end_date', [$startDate, $endDate])
            ->count();

        $activeValue = Contract::active()
            ->with('proposal')
            ->get()
            ->sum(fn($c) => $c->proposal?->price ?? 0);

        $byServiceType = DB::select("
            SELECT 
                st.label as service_type,
                COUNT(*) as count,
                SUM(p.price) as total_value
            FROM contracts c
            JOIN proposals p ON c.proposal_id = p.id
            JOIN domain_service_type st ON p.service_type_id = st.id
            WHERE c.signed_at BETWEEN ? AND ?
            GROUP BY st.id, st.label
            ORDER BY count DESC
        ", [$startDate, $endDate]);

        return [
            'signed' => $signed,
            'closed' => $closed,
            'active_value' => round($activeValue, 2),
            'by_service_type' => $byServiceType,
        ];
    }

    /**
     * Relatório de clientes por cidade
     */
    public function getClientsByCityReport(): array
    {
        return Client::selectRaw('city, COUNT(*) as count')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(20)
            ->pluck('count', 'city')
            ->toArray();
    }

    /**
     * Relatório de tipos de serviço mais demandados
     */
    public function getServiceTypesReport($startDate, $endDate): array
    {
        $data = Lead::selectRaw('
            service_type_id,
            COUNT(*) as total,
            SUM(CASE WHEN status_id = ? THEN 1 ELSE 0 END) as converted
        ', [DomainLeadStatus::ACTIVE])
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('service_type_id')
        ->with('serviceType')
        ->get();

        return $data->map(function ($item) {
            return [
                'service_type' => $item->serviceType->label ?? 'Desconhecido',
                'total' => $item->total,
                'converted' => $item->converted,
                'conversion_rate' => $item->total > 0 
                    ? round(($item->converted / $item->total) * 100, 2) 
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Inicia exportação de relatório (assíncrono)
     */
    public function exportReport(string $report, string $format, $startDate, $endDate, $user)
    {
        return ExportReportJob::dispatch(
            $report,
            $format,
            $startDate,
            $endDate,
            $user->id
        );
    }
}
