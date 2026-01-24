<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignMetric;
use App\Models\BudgetLimit;
use App\Models\BudgetAlert;
use App\Models\Domain\DomainCampaignStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Serviço de controle de orçamento.
 * 
 * Monitora gastos, aplica limites e dispara alertas
 * quando thresholds são atingidos.
 */
class BudgetControlService
{
    /**
     * Verifica e dispara alertas de orçamento.
     */
    public function checkAndAlert(): array
    {
        $alerts = [];

        // Verifica limite global
        $globalAlerts = $this->checkGlobalLimits();
        $alerts = array_merge($alerts, $globalAlerts);

        // Verifica limites por campanha
        $campaignAlerts = $this->checkCampaignLimits();
        $alerts = array_merge($alerts, $campaignAlerts);

        return $alerts;
    }

    /**
     * Verifica limites globais.
     */
    private function checkGlobalLimits(): array
    {
        $alerts = [];
        $globalLimit = BudgetLimit::getGlobal();

        if (!$globalLimit) {
            return $alerts;
        }

        // Gasto diário total
        $dailySpend = $this->getTotalDailySpend();
        if ($globalLimit->daily_limit) {
            $alerts = array_merge($alerts, $this->checkThresholds(
                null,
                $globalLimit,
                $dailySpend,
                $globalLimit->daily_limit,
                'daily'
            ));
        }

        // Gasto mensal total
        $monthlySpend = $this->getTotalMonthlySpend();
        if ($globalLimit->monthly_limit) {
            $alerts = array_merge($alerts, $this->checkThresholds(
                null,
                $globalLimit,
                $monthlySpend,
                $globalLimit->monthly_limit,
                'monthly'
            ));
        }

        return $alerts;
    }

    /**
     * Verifica limites por campanha.
     */
    private function checkCampaignLimits(): array
    {
        $alerts = [];

        $activeCampaigns = Campaign::active()->get();

        foreach ($activeCampaigns as $campaign) {
            $limit = BudgetLimit::forCampaign($campaign->id)->first();

            if (!$limit) {
                continue;
            }

            // Gasto diário da campanha
            $dailySpend = $this->getCampaignDailySpend($campaign->id);
            if ($limit->daily_limit) {
                $campaignAlerts = $this->checkThresholds(
                    $campaign->id,
                    $limit,
                    $dailySpend,
                    $limit->daily_limit,
                    'daily'
                );
                $alerts = array_merge($alerts, $campaignAlerts);

                // Auto-pause se configurado
                if ($limit->auto_pause_enabled && $dailySpend >= $limit->daily_limit) {
                    $this->autoPauseCampaign($campaign);
                }
            }

            // Gasto mensal da campanha
            $monthlySpend = $this->getCampaignMonthlySpend($campaign->id);
            if ($limit->monthly_limit) {
                $campaignAlerts = $this->checkThresholds(
                    $campaign->id,
                    $limit,
                    $monthlySpend,
                    $limit->monthly_limit,
                    'monthly'
                );
                $alerts = array_merge($alerts, $campaignAlerts);

                // Auto-pause se configurado
                if ($limit->auto_pause_enabled && $monthlySpend >= $limit->monthly_limit) {
                    $this->autoPauseCampaign($campaign);
                }
            }

            // Gasto total da campanha
            $totalSpend = $this->getCampaignTotalSpend($campaign->id);
            if ($limit->total_limit) {
                $campaignAlerts = $this->checkThresholds(
                    $campaign->id,
                    $limit,
                    $totalSpend,
                    $limit->total_limit,
                    'total'
                );
                $alerts = array_merge($alerts, $campaignAlerts);

                // Auto-pause se configurado
                if ($limit->auto_pause_enabled && $totalSpend >= $limit->total_limit) {
                    $this->autoPauseCampaign($campaign);
                }
            }
        }

        return $alerts;
    }

    /**
     * Verifica thresholds e cria alertas.
     */
    private function checkThresholds(
        ?int $campaignId,
        BudgetLimit $limit,
        float $currentSpend,
        float $limitValue,
        string $periodType
    ): array {
        $alerts = [];
        $thresholds = [70, 90, 100];
        $periodDate = today()->toDateString();

        foreach ($thresholds as $threshold) {
            if (!$limit->shouldAlertAt($threshold)) {
                continue;
            }

            $thresholdValue = $limitValue * ($threshold / 100);

            if ($currentSpend >= $thresholdValue) {
                // Verifica se alerta já existe
                if (BudgetAlert::alreadyExists($campaignId, $threshold, $periodType, $periodDate)) {
                    continue;
                }

                $alert = BudgetAlert::createAlert(
                    $campaignId,
                    $limit->id,
                    $threshold,
                    $currentSpend,
                    $limitValue,
                    $periodType
                );

                $alerts[] = $alert;

                Log::warning('Budget alert triggered', [
                    'campaign_id' => $campaignId,
                    'threshold' => $threshold,
                    'current_spend' => $currentSpend,
                    'limit' => $limitValue,
                    'period_type' => $periodType,
                ]);
            }
        }

        return $alerts;
    }

    /**
     * Pausa campanha automaticamente.
     */
    private function autoPauseCampaign(Campaign $campaign): void
    {
        $campaign->update(['status_id' => DomainCampaignStatus::PAUSED]);

        Log::warning('Campaign auto-paused due to budget limit', [
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
        ]);
    }

    /**
     * Obtém gasto diário total.
     */
    public function getTotalDailySpend(): float
    {
        return CampaignMetric::whereDate('metric_date', today())
            ->sum('spend') ?? 0;
    }

    /**
     * Obtém gasto mensal total.
     */
    public function getTotalMonthlySpend(): float
    {
        return CampaignMetric::thisMonth()->sum('spend') ?? 0;
    }

    /**
     * Obtém gasto diário da campanha.
     */
    public function getCampaignDailySpend(int $campaignId): float
    {
        return CampaignMetric::where('campaign_id', $campaignId)
            ->whereDate('metric_date', today())
            ->sum('spend') ?? 0;
    }

    /**
     * Obtém gasto mensal da campanha.
     */
    public function getCampaignMonthlySpend(int $campaignId): float
    {
        return CampaignMetric::where('campaign_id', $campaignId)
            ->thisMonth()
            ->sum('spend') ?? 0;
    }

    /**
     * Obtém gasto total da campanha.
     */
    public function getCampaignTotalSpend(int $campaignId): float
    {
        return CampaignMetric::where('campaign_id', $campaignId)
            ->sum('spend') ?? 0;
    }

    /**
     * Define limite para campanha.
     */
    public function setLimit(int $campaignId, array $limits): BudgetLimit
    {
        return BudgetLimit::updateOrCreate(
            ['campaign_id' => $campaignId],
            [
                'daily_limit' => $limits['daily_limit'] ?? null,
                'monthly_limit' => $limits['monthly_limit'] ?? null,
                'total_limit' => $limits['total_limit'] ?? null,
                'auto_pause_enabled' => $limits['auto_pause_enabled'] ?? false,
                'alert_threshold_70' => $limits['alert_threshold_70'] ?? true,
                'alert_threshold_90' => $limits['alert_threshold_90'] ?? true,
                'alert_threshold_100' => $limits['alert_threshold_100'] ?? true,
            ]
        );
    }

    /**
     * Define limite global.
     */
    public function setGlobalLimit(array $limits): BudgetLimit
    {
        return BudgetLimit::updateOrCreate(
            ['campaign_id' => null],
            [
                'daily_limit' => $limits['daily_limit'] ?? null,
                'monthly_limit' => $limits['monthly_limit'] ?? null,
                'total_limit' => $limits['total_limit'] ?? null,
                'auto_pause_enabled' => $limits['auto_pause_enabled'] ?? false,
                'alert_threshold_70' => $limits['alert_threshold_70'] ?? true,
                'alert_threshold_90' => $limits['alert_threshold_90'] ?? true,
                'alert_threshold_100' => $limits['alert_threshold_100'] ?? true,
            ]
        );
    }

    /**
     * Obtém limite de campanha.
     */
    public function getLimit(int $campaignId): ?BudgetLimit
    {
        return BudgetLimit::forCampaign($campaignId)->first();
    }

    /**
     * Obtém limite global.
     */
    public function getGlobalLimit(): ?BudgetLimit
    {
        return BudgetLimit::getGlobal();
    }

    /**
     * Lista alertas não reconhecidos.
     */
    public function getUnacknowledgedAlerts(): array
    {
        return BudgetAlert::with('campaign')
            ->unacknowledged()
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Reconhece alerta.
     */
    public function acknowledgeAlert(int $alertId): BudgetAlert
    {
        $alert = BudgetAlert::findOrFail($alertId);
        return $alert->acknowledge();
    }

    /**
     * Obtém resumo de orçamento.
     */
    public function getBudgetSummary(): array
    {
        $globalLimit = $this->getGlobalLimit();

        return [
            'global' => [
                'daily_limit' => $globalLimit?->daily_limit,
                'monthly_limit' => $globalLimit?->monthly_limit,
                'daily_spend' => $this->getTotalDailySpend(),
                'monthly_spend' => $this->getTotalMonthlySpend(),
                'daily_usage_percent' => $globalLimit?->calculateUsagePercent(
                    $this->getTotalDailySpend(),
                    'daily'
                ),
                'monthly_usage_percent' => $globalLimit?->calculateUsagePercent(
                    $this->getTotalMonthlySpend(),
                    'monthly'
                ),
            ],
            'alerts_pending' => BudgetAlert::unacknowledged()->count(),
            'campaigns_with_limits' => BudgetLimit::whereNotNull('campaign_id')->count(),
        ];
    }
}
