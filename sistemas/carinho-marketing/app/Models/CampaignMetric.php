<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Metricas de campanha (impressoes, cliques, gastos, leads).
 *
 * @property int $id
 * @property int $campaign_id
 * @property \Carbon\Carbon $metric_date
 * @property int $impressions
 * @property int $clicks
 * @property float $spend
 * @property int $leads
 */
class CampaignMetric extends Model
{
    public $timestamps = false;

    protected $table = 'campaign_metrics';

    protected $fillable = [
        'campaign_id',
        'metric_date',
        'impressions',
        'clicks',
        'spend',
        'leads',
    ];

    protected $casts = [
        'campaign_id' => 'integer',
        'metric_date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'spend' => 'decimal:2',
        'leads' => 'integer',
    ];

    /**
     * Relacionamento com campanha.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /**
     * Calcula CTR (Click Through Rate).
     */
    public function getCtrAttribute(): ?float
    {
        if ($this->impressions === 0) {
            return null;
        }

        return round(($this->clicks / $this->impressions) * 100, 2);
    }

    /**
     * Calcula CPC (Cost Per Click).
     */
    public function getCpcAttribute(): ?float
    {
        if ($this->clicks === 0) {
            return null;
        }

        return round($this->spend / $this->clicks, 2);
    }

    /**
     * Calcula CPM (Cost Per Mille).
     */
    public function getCpmAttribute(): ?float
    {
        if ($this->impressions === 0) {
            return null;
        }

        return round(($this->spend / $this->impressions) * 1000, 2);
    }

    /**
     * Calcula CPL (Cost Per Lead).
     */
    public function getCplAttribute(): ?float
    {
        if ($this->leads === 0) {
            return null;
        }

        return round($this->spend / $this->leads, 2);
    }

    /**
     * Calcula taxa de conversao.
     */
    public function getConversionRateAttribute(): ?float
    {
        if ($this->clicks === 0) {
            return null;
        }

        return round(($this->leads / $this->clicks) * 100, 2);
    }

    /**
     * Scope por periodo.
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    /**
     * Scope para hoje.
     */
    public function scopeToday($query)
    {
        return $query->where('metric_date', now()->toDateString());
    }

    /**
     * Scope para esta semana.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('metric_date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString(),
        ]);
    }

    /**
     * Scope para este mes.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('metric_date', [
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        ]);
    }

    /**
     * Agrupa metricas por campanha.
     */
    public static function aggregateByCampaign(int $campaignId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = self::where('campaign_id', $campaignId);

        if ($startDate && $endDate) {
            $query->whereBetween('metric_date', [$startDate, $endDate]);
        }

        $result = $query->selectRaw('
            SUM(impressions) as total_impressions,
            SUM(clicks) as total_clicks,
            SUM(spend) as total_spend,
            SUM(leads) as total_leads
        ')->first();

        $data = [
            'impressions' => (int) ($result->total_impressions ?? 0),
            'clicks' => (int) ($result->total_clicks ?? 0),
            'spend' => (float) ($result->total_spend ?? 0),
            'leads' => (int) ($result->total_leads ?? 0),
        ];

        // Calcular metricas derivadas
        $data['ctr'] = $data['impressions'] > 0
            ? round(($data['clicks'] / $data['impressions']) * 100, 2)
            : null;

        $data['cpc'] = $data['clicks'] > 0
            ? round($data['spend'] / $data['clicks'], 2)
            : null;

        $data['cpl'] = $data['leads'] > 0
            ? round($data['spend'] / $data['leads'], 2)
            : null;

        $data['conversion_rate'] = $data['clicks'] > 0
            ? round(($data['leads'] / $data['clicks']) * 100, 2)
            : null;

        return $data;
    }

    /**
     * Cria ou atualiza metrica para uma data.
     */
    public static function upsert(int $campaignId, string $date, array $data): self
    {
        return self::updateOrCreate(
            [
                'campaign_id' => $campaignId,
                'metric_date' => $date,
            ],
            [
                'impressions' => $data['impressions'] ?? 0,
                'clicks' => $data['clicks'] ?? 0,
                'spend' => $data['spend'] ?? 0,
                'leads' => $data['leads'] ?? 0,
            ]
        );
    }
}
