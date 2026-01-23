<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Modelo de Transação Financeira (Fluxo de Caixa).
 *
 * Representa uma movimentação financeira no sistema,
 * permitindo controle detalhado de entradas e saídas.
 */
class CashTransaction extends Model
{
    use LogsActivity;

    protected $table = 'cash_transactions';

    protected $fillable = [
        'transaction_date',
        'competence_date',
        'type_id',
        'category_id',
        'description',
        'amount',
        'direction',
        'reference_type',
        'reference_id',
        'bank_account_id',
        'external_reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'competence_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Direções
    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    /**
     * Relacionamento com tipo de transação.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DomainTransactionType::class, 'type_id');
    }

    /**
     * Relacionamento com categoria financeira.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DomainFinancialCategory::class, 'category_id');
    }

    /**
     * Relacionamento com conta bancária.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    /**
     * Relacionamento polimórfico com a referência.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    /**
     * Verifica se é entrada.
     */
    public function isIncome(): bool
    {
        return $this->direction === self::DIRECTION_IN;
    }

    /**
     * Verifica se é saída.
     */
    public function isExpense(): bool
    {
        return $this->direction === self::DIRECTION_OUT;
    }

    /**
     * Obtém valor com sinal (positivo para entrada, negativo para saída).
     */
    public function getSignedAmountAttribute(): float
    {
        return $this->isIncome() ? (float) $this->amount : -1 * (float) $this->amount;
    }

    /**
     * Scope para entradas.
     */
    public function scopeIncome($query)
    {
        return $query->where('direction', self::DIRECTION_IN);
    }

    /**
     * Scope para saídas.
     */
    public function scopeExpense($query)
    {
        return $query->where('direction', self::DIRECTION_OUT);
    }

    /**
     * Scope por período de transação.
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope por período de competência.
     */
    public function scopeForCompetencePeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('competence_date', [$startDate, $endDate]);
    }

    /**
     * Scope por categoria.
     */
    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope por tipo.
     */
    public function scopeForType($query, int $typeId)
    {
        return $query->where('type_id', $typeId);
    }

    /**
     * Configurações de log de atividade.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount', 'direction', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
