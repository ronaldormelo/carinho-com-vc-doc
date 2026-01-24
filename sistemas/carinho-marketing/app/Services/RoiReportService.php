<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignMetric;
use App\Models\LeadSource;
use App\Models\PartnershipReferral;
use App\Models\CustomerReferral;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Serviço de relatório de ROI consolidado.
 * 
 * Calcula retorno sobre investimento considerando
 * ticket médio, recorrência e diferentes canais.
 */
class RoiReportService
{
    /**
     * Ticket médio padrão para cálculos (R$).
     */
    private const DEFAULT_TICKET = 2500.00;

    /**
     * Meses médios de recorrência.
     */
    private const RECURRENCE_MONTHS = 6;

    /**
     * Obtém relatório de ROI consolidado.
     */
    public function getConsolidatedReport(
        string $startDate,
        string $endDate,
        ?float $averageTicket = null,
        ?int $recurrenceMonths = null
    ): array {
        $averageTicket = $averageTicket ?? self::DEFAULT_TICKET;
        $recurrenceMonths = $recurrenceMonths ?? self::RECURRENCE_MONTHS;

        $cacheKey = "roi_report_{$startDate}_{$endDate}";

        return Cache::remember($cacheKey, 1800, function () use (
            $startDate,
            $endDate,
            $averageTicket,
            $recurrenceMonths
        ) {
            return [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'parameters' => [
                    'average_ticket' => $averageTicket,
                    'recurrence_months' => $recurrenceMonths,
                    'lifetime_value' => $averageTicket * $recurrenceMonths,
                ],
                'summary' => $this->getSummary($startDate, $endDate, $averageTicket, $recurrenceMonths),
                'by_channel' => $this->getRoiByChannel($startDate, $endDate, $averageTicket, $recurrenceMonths),
                'by_source' => $this->getRoiBySource($startDate, $endDate, $averageTicket, $recurrenceMonths),
                'campaigns' => $this->getCampaignPerformance($startDate, $endDate, $averageTicket, $recurrenceMonths),
                'trends' => $this->getTrends($startDate, $endDate),
            ];
        });
    }

    /**
     * Obtém resumo geral.
     */
    private function getSummary(
        string $startDate,
        string $endDate,
        float $averageTicket,
        int $recurrenceMonths
    ): array {
        $metrics = CampaignMetric::inPeriod($startDate, $endDate)
            ->selectRaw('
                SUM(impressions) as total_impressions,
                SUM(clicks) as total_clicks,
                SUM(spend) as total_spend,
                SUM(leads) as total_leads
            ')
            ->first();

        $totalSpend = (float) ($metrics->total_spend ?? 0);
        $totalLeads = (int) ($metrics->total_leads ?? 0);
        $ltv = $averageTicket * $recurrenceMonths;

        // Leads de indicação (custo zero)
        $referralLeads = $this->getReferralLeadsCount($startDate, $endDate);
        $partnershipLeads = $this->getPartnershipLeadsCount($startDate, $endDate);
        $organicLeads = $this->getOrganicLeadsCount($startDate, $endDate);

        $totalLeadsAllChannels = $totalLeads + $referralLeads + $partnershipLeads + $organicLeads;

        // Estimativa de receita (considerando taxa de conversão de 20%)
        $conversionRate = 0.20;
        $estimatedConversions = $totalLeadsAllChannels * $conversionRate;
        $estimatedRevenue = $estimatedConversions * $ltv;

        // ROI = (Receita - Custo) / Custo * 100
        $roi = $totalSpend > 0 
            ? round((($estimatedRevenue - $totalSpend) / $totalSpend) * 100, 2)
            : null;

        return [
            'total_spend' => $totalSpend,
            'total_leads' => $totalLeadsAllChannels,
            'leads_paid' => $totalLeads,
            'leads_referral' => $referralLeads,
            'leads_partnership' => $partnershipLeads,
            'leads_organic' => $organicLeads,
            'cpl_paid' => $totalLeads > 0 ? round($totalSpend / $totalLeads, 2) : null,
            'cpl_all' => $totalLeadsAllChannels > 0 ? round($totalSpend / $totalLeadsAllChannels, 2) : null,
            'estimated_conversions' => (int) $estimatedConversions,
            'estimated_revenue' => $estimatedRevenue,
            'estimated_cac' => $estimatedConversions > 0 ? round($totalSpend / $estimatedConversions, 2) : null,
            'roi_percent' => $roi,
            'payback_days' => $this->calculatePaybackDays($totalSpend, $estimatedRevenue, $startDate, $endDate),
        ];
    }

    /**
     * Obtém ROI por canal de marketing.
     */
    private function getRoiByChannel(
        string $startDate,
        string $endDate,
        float $averageTicket,
        int $recurrenceMonths
    ): array {
        $channels = DB::table('campaign_metrics')
            ->join('campaigns', 'campaign_metrics.campaign_id', '=', 'campaigns.id')
            ->join('marketing_channels', 'campaigns.channel_id', '=', 'marketing_channels.id')
            ->whereBetween('campaign_metrics.metric_date', [$startDate, $endDate])
            ->groupBy('marketing_channels.id', 'marketing_channels.name')
            ->select(
                'marketing_channels.id',
                'marketing_channels.name',
                DB::raw('SUM(campaign_metrics.spend) as total_spend'),
                DB::raw('SUM(campaign_metrics.leads) as total_leads'),
                DB::raw('SUM(campaign_metrics.clicks) as total_clicks'),
                DB::raw('SUM(campaign_metrics.impressions) as total_impressions')
            )
            ->get();

        $ltv = $averageTicket * $recurrenceMonths;
        $conversionRate = 0.20;

        return $channels->map(function ($channel) use ($ltv, $conversionRate) {
            $spend = (float) $channel->total_spend;
            $leads = (int) $channel->total_leads;
            $conversions = $leads * $conversionRate;
            $revenue = $conversions * $ltv;
            $roi = $spend > 0 ? round((($revenue - $spend) / $spend) * 100, 2) : null;

            return [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'spend' => $spend,
                'leads' => $leads,
                'clicks' => (int) $channel->total_clicks,
                'impressions' => (int) $channel->total_impressions,
                'cpl' => $leads > 0 ? round($spend / $leads, 2) : null,
                'ctr' => (int) $channel->total_impressions > 0 
                    ? round(((int) $channel->total_clicks / (int) $channel->total_impressions) * 100, 2) 
                    : null,
                'estimated_conversions' => (int) $conversions,
                'estimated_revenue' => $revenue,
                'roi_percent' => $roi,
            ];
        })->toArray();
    }

    /**
     * Obtém ROI por fonte UTM.
     */
    private function getRoiBySource(
        string $startDate,
        string $endDate,
        float $averageTicket,
        int $recurrenceMonths
    ): array {
        $sources = LeadSource::inPeriod($startDate, $endDate)
            ->selectRaw('
                utm_source,
                utm_medium,
                COUNT(*) as total_leads
            ')
            ->groupBy('utm_source', 'utm_medium')
            ->orderBy('total_leads', 'desc')
            ->limit(20)
            ->get();

        $ltv = $averageTicket * $recurrenceMonths;
        $conversionRate = 0.20;

        return $sources->map(function ($source) use ($ltv, $conversionRate) {
            $leads = (int) $source->total_leads;
            $conversions = $leads * $conversionRate;
            $revenue = $conversions * $ltv;

            // Para fontes orgânicas/indicação, custo é zero
            $isOrganic = in_array($source->utm_source, ['indicacao', 'parceria', 'organico', 'direct']);
            $estimatedSpend = $isOrganic ? 0 : null; // null = não temos dados de custo por fonte

            return [
                'source' => $source->utm_source ?? 'direct',
                'medium' => $source->utm_medium ?? 'none',
                'leads' => $leads,
                'estimated_conversions' => (int) $conversions,
                'estimated_revenue' => $revenue,
                'is_organic' => $isOrganic,
            ];
        })->toArray();
    }

    /**
     * Obtém performance das campanhas.
     */
    private function getCampaignPerformance(
        string $startDate,
        string $endDate,
        float $averageTicket,
        int $recurrenceMonths
    ): array {
        $campaigns = Campaign::with('channel')
            ->whereHas('metrics', function ($query) use ($startDate, $endDate) {
                $query->inPeriod($startDate, $endDate);
            })
            ->get();

        $ltv = $averageTicket * $recurrenceMonths;
        $conversionRate = 0.20;

        return $campaigns->map(function ($campaign) use ($startDate, $endDate, $ltv, $conversionRate) {
            $metrics = CampaignMetric::aggregateByCampaign($campaign->id, $startDate, $endDate);

            $spend = (float) ($metrics['total_spend'] ?? 0);
            $leads = (int) ($metrics['total_leads'] ?? 0);
            $conversions = $leads * $conversionRate;
            $revenue = $conversions * $ltv;
            $roi = $spend > 0 ? round((($revenue - $spend) / $spend) * 100, 2) : null;

            return [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'channel' => $campaign->channel?->name,
                'spend' => $spend,
                'leads' => $leads,
                'cpl' => $leads > 0 ? round($spend / $leads, 2) : null,
                'estimated_conversions' => (int) $conversions,
                'estimated_revenue' => $revenue,
                'roi_percent' => $roi,
                'efficiency_rating' => $this->calculateEfficiencyRating($roi),
            ];
        })
        ->sortByDesc('roi_percent')
        ->values()
        ->toArray();
    }

    /**
     * Obtém tendências mensais.
     */
    private function getTrends(string $startDate, string $endDate): array
    {
        return CampaignMetric::inPeriod($startDate, $endDate)
            ->selectRaw('
                DATE_FORMAT(metric_date, "%Y-%m") as month,
                SUM(spend) as total_spend,
                SUM(leads) as total_leads,
                SUM(clicks) as total_clicks
            ')
            ->groupBy(DB::raw('DATE_FORMAT(metric_date, "%Y-%m")'))
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'spend' => (float) $item->total_spend,
                    'leads' => (int) $item->total_leads,
                    'clicks' => (int) $item->total_clicks,
                    'cpl' => (int) $item->total_leads > 0 
                        ? round((float) $item->total_spend / (int) $item->total_leads, 2)
                        : null,
                ];
            })
            ->toArray();
    }

    /**
     * Conta leads de indicação de clientes.
     */
    private function getReferralLeadsCount(string $startDate, string $endDate): int
    {
        return CustomerReferral::inPeriod($startDate, $endDate)
            ->whereNotNull('referred_lead_id')
            ->count();
    }

    /**
     * Conta leads de parcerias.
     */
    private function getPartnershipLeadsCount(string $startDate, string $endDate): int
    {
        return PartnershipReferral::inPeriod($startDate, $endDate)->count();
    }

    /**
     * Conta leads orgânicos.
     */
    private function getOrganicLeadsCount(string $startDate, string $endDate): int
    {
        return LeadSource::inPeriod($startDate, $endDate)
            ->where(function ($query) {
                $query->whereIn('utm_source', ['organico', 'direct', 'google_organic'])
                    ->orWhereNull('utm_source');
            })
            ->count();
    }

    /**
     * Calcula dias para payback.
     */
    private function calculatePaybackDays(
        float $totalSpend,
        float $estimatedRevenue,
        string $startDate,
        string $endDate
    ): ?int {
        if ($totalSpend <= 0 || $estimatedRevenue <= 0) {
            return null;
        }

        $periodDays = (strtotime($endDate) - strtotime($startDate)) / 86400;
        $revenuePerDay = $estimatedRevenue / max($periodDays, 1);

        if ($revenuePerDay <= 0) {
            return null;
        }

        return (int) ceil($totalSpend / $revenuePerDay);
    }

    /**
     * Calcula rating de eficiência.
     */
    private function calculateEfficiencyRating(?float $roi): string
    {
        if ($roi === null) {
            return 'N/A';
        }

        if ($roi >= 300) {
            return 'Excelente';
        } elseif ($roi >= 200) {
            return 'Muito Bom';
        } elseif ($roi >= 100) {
            return 'Bom';
        } elseif ($roi >= 0) {
            return 'Regular';
        } else {
            return 'Negativo';
        }
    }

    /**
     * Obtém comparativo entre períodos.
     */
    public function getComparison(
        string $currentStart,
        string $currentEnd,
        string $previousStart,
        string $previousEnd
    ): array {
        $current = $this->getConsolidatedReport($currentStart, $currentEnd);
        $previous = $this->getConsolidatedReport($previousStart, $previousEnd);

        $currentSummary = $current['summary'];
        $previousSummary = $previous['summary'];

        return [
            'current_period' => [
                'start_date' => $currentStart,
                'end_date' => $currentEnd,
                'summary' => $currentSummary,
            ],
            'previous_period' => [
                'start_date' => $previousStart,
                'end_date' => $previousEnd,
                'summary' => $previousSummary,
            ],
            'variation' => [
                'spend' => $this->calculateVariation(
                    $currentSummary['total_spend'],
                    $previousSummary['total_spend']
                ),
                'leads' => $this->calculateVariation(
                    $currentSummary['total_leads'],
                    $previousSummary['total_leads']
                ),
                'cpl' => $this->calculateVariation(
                    $currentSummary['cpl_all'],
                    $previousSummary['cpl_all']
                ),
                'roi' => $this->calculateVariation(
                    $currentSummary['roi_percent'],
                    $previousSummary['roi_percent']
                ),
            ],
        ];
    }

    /**
     * Calcula variação percentual.
     */
    private function calculateVariation($current, $previous): ?float
    {
        if ($previous === null || $previous == 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
