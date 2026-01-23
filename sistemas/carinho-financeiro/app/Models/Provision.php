<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Modelo de Provisão.
 *
 * Representa provisões contábeis, como PCLD (Provisão para Créditos
 * de Liquidação Duvidosa) para estimativa de perdas com inadimplência.
 */
class Provision extends Model
{
    use LogsActivity;

    protected $table = 'provisions';

    protected $fillable = [
        'period',
        'type',
        'calculated_amount',
        'adjusted_amount',
        'used_amount',
        'calculation_base',
        'notes',
        'created_by',
        'adjusted_by',
        'adjusted_at',
    ];

    protected $casts = [
        'calculated_amount' => 'decimal:2',
        'adjusted_amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'calculation_base' => 'array',
        'adjusted_at' => 'datetime',
    ];

    // Tipos de provisão
    public const TYPE_PCLD = 'pcld';
    public const TYPE_OTHER = 'other';

    /**
     * Obtém o valor efetivo da provisão.
     */
    public function getEffectiveAmountAttribute(): float
    {
        return $this->adjusted_amount !== null 
            ? (float) $this->adjusted_amount 
            : (float) $this->calculated_amount;
    }

    /**
     * Obtém o saldo disponível.
     */
    public function getBalanceAttribute(): float
    {
        return $this->effective_amount - (float) ($this->used_amount ?? 0);
    }

    /**
     * Verifica se tem saldo disponível.
     */
    public function hasBalance(): bool
    {
        return $this->balance > 0;
    }

    /**
     * Utiliza parte da provisão.
     */
    public function use(float $amount): self
    {
        if ($amount > $this->balance) {
            throw new \Exception('Valor excede o saldo da provisão');
        }

        $this->used_amount = (float) ($this->used_amount ?? 0) + $amount;
        $this->save();

        return $this;
    }

    /**
     * Ajusta o valor da provisão.
     */
    public function adjust(float $amount, string $adjustedBy, ?string $notes = null): self
    {
        $this->adjusted_amount = $amount;
        $this->adjusted_by = $adjustedBy;
        $this->adjusted_at = now();
        
        if ($notes) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . $notes;
        }

        $this->save();

        return $this;
    }

    /**
     * Scope por tipo.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para PCLD.
     */
    public function scopePcld($query)
    {
        return $query->where('type', self::TYPE_PCLD);
    }

    /**
     * Scope por período.
     */
    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Scope com saldo.
     */
    public function scopeWithBalance($query)
    {
        return $query->whereRaw('COALESCE(adjusted_amount, calculated_amount) > COALESCE(used_amount, 0)');
    }

    /**
     * Configurações de log de atividade.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['calculated_amount', 'adjusted_amount', 'used_amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
