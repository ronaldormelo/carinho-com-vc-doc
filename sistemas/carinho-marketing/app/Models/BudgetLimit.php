<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model para configuração de limites de gastos.
 * 
 * Define limites diários, mensais e totais para campanhas
 * ou para toda a conta de marketing (quando campaign_id é null).
 */
class BudgetLimit extends Model
{
    protected $table = 'budget_limits';

    protected $fillable = [
        'campaign_id',
        'daily_limit',
        'monthly_limit',
        'total_limit',
        'auto_pause_enabled',
        'alert_threshold_70',
        'alert_threshold_90',
        'alert_threshold_100',
    ];

    protected $casts = [
        'daily_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2',
        'total_limit' => 'decimal:2',
        'auto_pause_enabled' => 'boolean',
        'alert_threshold_70' => 'boolean',
        'alert_threshold_90' => 'boolean',
        'alert_threshold_100' => 'boolean',
    ];

    /**
     * Relacionamento com campanha.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Relacionamento com alertas.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(BudgetAlert::class);
    }

    /**
     * Verifica se é limite global.
     */
    public function isGlobal(): bool
    {
        return $this->campaign_id === null;
    }

    /**
     * Scope para limite global.
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('campaign_id');
    }

    /**
     * Scope para limite de campanha específica.
     */
    public function scopeForCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Obtém limite global.
     */
    public static function getGlobal(): ?self
    {
        return self::global()->first();
    }

    /**
     * Obtém ou cria limite para campanha.
     */
    public static function getOrCreateForCampaign(int $campaignId): self
    {
        return self::firstOrCreate(
            ['campaign_id' => $campaignId],
            [
                'daily_limit' => null,
                'monthly_limit' => null,
                'total_limit' => null,
                'auto_pause_enabled' => false,
            ]
        );
    }

    /**
     * Verifica se deve alertar no threshold.
     */
    public function shouldAlertAt(int $threshold): bool
    {
        return match ($threshold) {
            70 => (bool) $this->alert_threshold_70,
            90 => (bool) $this->alert_threshold_90,
            100 => (bool) $this->alert_threshold_100,
            default => false,
        };
    }

    /**
     * Calcula porcentagem de uso.
     */
    public function calculateUsagePercent(float $currentSpend, string $periodType): ?float
    {
        $limit = match ($periodType) {
            'daily' => $this->daily_limit,
            'monthly' => $this->monthly_limit,
            'total' => $this->total_limit,
            default => null,
        };

        if (!$limit || $limit <= 0) {
            return null;
        }

        return round(($currentSpend / $limit) * 100, 2);
    }
}
