<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Modelo de Contas a Pagar.
 *
 * Representa uma obrigação de pagamento da empresa,
 * seja para fornecedores, cuidadores ou outras despesas.
 */
class Payable extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'payables';

    protected $fillable = [
        'status_id',
        'category_id',
        'supplier_name',
        'supplier_document',
        'description',
        'amount',
        'discount_amount',
        'interest_amount',
        'paid_amount',
        'issue_date',
        'due_date',
        'competence_date',
        'paid_at',
        'bank_account_id',
        'payment_method',
        'document_number',
        'barcode',
        'notes',
        'reference_type',
        'reference_id',
        'created_by',
        'paid_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'competence_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainPayableStatus::class, 'status_id');
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
     * Calcula o valor líquido a pagar.
     */
    public function getNetAmountAttribute(): float
    {
        return (float) $this->amount 
            - (float) ($this->discount_amount ?? 0) 
            + (float) ($this->interest_amount ?? 0);
    }

    /**
     * Verifica se está em aberto.
     */
    public function isOpen(): bool
    {
        return $this->status_id === DomainPayableStatus::OPEN;
    }

    /**
     * Verifica se está pago.
     */
    public function isPaid(): bool
    {
        return $this->status_id === DomainPayableStatus::PAID;
    }

    /**
     * Verifica se está vencido.
     */
    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_date < now()->startOfDay();
    }

    /**
     * Verifica se pode ser pago.
     */
    public function canBePaid(): bool
    {
        return in_array($this->status_id, [
            DomainPayableStatus::OPEN,
            DomainPayableStatus::SCHEDULED,
        ]);
    }

    /**
     * Marca como pago.
     */
    public function markAsPaid(float $amount, ?string $paidBy = null): self
    {
        $this->status_id = DomainPayableStatus::PAID;
        $this->paid_amount = $amount;
        $this->paid_at = now();
        $this->paid_by = $paidBy;
        $this->save();

        return $this;
    }

    /**
     * Cancela o pagamento.
     */
    public function cancel(): self
    {
        $this->status_id = DomainPayableStatus::CANCELED;
        $this->save();

        return $this;
    }

    /**
     * Scope para contas em aberto.
     */
    public function scopeOpen($query)
    {
        return $query->where('status_id', DomainPayableStatus::OPEN);
    }

    /**
     * Scope para contas pagas.
     */
    public function scopePaid($query)
    {
        return $query->where('status_id', DomainPayableStatus::PAID);
    }

    /**
     * Scope para contas vencidas.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status_id', DomainPayableStatus::OPEN)
            ->where('due_date', '<', now()->startOfDay());
    }

    /**
     * Scope por período de vencimento.
     */
    public function scopeDueBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    /**
     * Scope por categoria.
     */
    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Configurações de log de atividade.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status_id', 'amount', 'paid_amount', 'paid_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
