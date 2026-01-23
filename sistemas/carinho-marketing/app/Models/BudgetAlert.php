<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model para alertas de orçamento.
 * 
 * Registra alertas disparados quando gastos atingem
 * thresholds configurados (70%, 90%, 100%).
 */
class BudgetAlert extends Model
{
    protected $table = 'budget_alerts';
    
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'budget_limit_id',
        'threshold_percent',
        'current_spend',
        'limit_value',
        'period_type',
        'period_date',
        'acknowledged',
        'created_at',
    ];

    protected $casts = [
        'current_spend' => 'decimal:2',
        'limit_value' => 'decimal:2',
        'period_date' => 'date',
        'acknowledged' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Relacionamento com campanha.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Relacionamento com limite de orçamento.
     */
    public function budgetLimit(): BelongsTo
    {
        return $this->belongsTo(BudgetLimit::class);
    }

    /**
     * Verifica se é alerta global.
     */
    public function isGlobal(): bool
    {
        return $this->campaign_id === null;
    }

    /**
     * Scope para alertas não reconhecidos.
     */
    public function scopeUnacknowledged($query)
    {
        return $query->where('acknowledged', false);
    }

    /**
     * Scope para alertas de hoje.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('period_date', today());
    }

    /**
     * Scope para alertas do mês.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('period_date', now()->month)
            ->whereYear('period_date', now()->year);
    }

    /**
     * Cria alerta de orçamento.
     */
    public static function createAlert(
        ?int $campaignId,
        int $budgetLimitId,
        int $threshold,
        float $currentSpend,
        float $limitValue,
        string $periodType
    ): self {
        return self::create([
            'campaign_id' => $campaignId,
            'budget_limit_id' => $budgetLimitId,
            'threshold_percent' => $threshold,
            'current_spend' => $currentSpend,
            'limit_value' => $limitValue,
            'period_type' => $periodType,
            'period_date' => today(),
            'acknowledged' => false,
            'created_at' => now(),
        ]);
    }

    /**
     * Reconhece o alerta.
     */
    public function acknowledge(): self
    {
        $this->update(['acknowledged' => true]);
        return $this;
    }

    /**
     * Verifica se já existe alerta similar não reconhecido.
     */
    public static function alreadyExists(
        ?int $campaignId,
        int $threshold,
        string $periodType,
        string $periodDate
    ): bool {
        return self::where('campaign_id', $campaignId)
            ->where('threshold_percent', $threshold)
            ->where('period_type', $periodType)
            ->where('period_date', $periodDate)
            ->exists();
    }
}
