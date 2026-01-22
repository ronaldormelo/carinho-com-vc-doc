<?php

namespace App\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use LogsActivity;

    protected $table = 'invoices';

    protected $fillable = [
        'client_id',
        'contract_id',
        'period_start',
        'period_end',
        'status_id',
        'total_amount',
        'due_date',
        'notes',
        'cancellation_fee',
        'discount_amount',
        'external_reference',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'cancellation_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainInvoiceStatus::class, 'status_id');
    }

    /**
     * Itens da fatura.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    /**
     * Pagamentos desta fatura.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    /**
     * Documento fiscal vinculado.
     */
    public function fiscalDocument(): HasOne
    {
        return $this->hasOne(FiscalDocument::class, 'invoice_id');
    }

    /**
     * Pagamento bem-sucedido mais recente.
     */
    public function successfulPayment(): HasOne
    {
        return $this->hasOne(Payment::class, 'invoice_id')
            ->where('status_id', DomainPaymentStatus::PAID)
            ->latest('paid_at');
    }

    /**
     * Verifica se está aberta.
     */
    public function isOpen(): bool
    {
        return $this->status_id === DomainInvoiceStatus::OPEN;
    }

    /**
     * Verifica se está paga.
     */
    public function isPaid(): bool
    {
        return $this->status_id === DomainInvoiceStatus::PAID;
    }

    /**
     * Verifica se está vencida.
     */
    public function isOverdue(): bool
    {
        return $this->status_id === DomainInvoiceStatus::OVERDUE;
    }

    /**
     * Verifica se está cancelada.
     */
    public function isCanceled(): bool
    {
        return $this->status_id === DomainInvoiceStatus::CANCELED;
    }

    /**
     * Verifica se pode ser paga.
     */
    public function canBePaid(): bool
    {
        return in_array($this->status_id, [
            DomainInvoiceStatus::OPEN,
            DomainInvoiceStatus::OVERDUE,
        ]);
    }

    /**
     * Verifica se pode ser cancelada.
     */
    public function canBeCanceled(): bool
    {
        return in_array($this->status_id, [
            DomainInvoiceStatus::OPEN,
            DomainInvoiceStatus::OVERDUE,
        ]);
    }

    /**
     * Marca como paga.
     */
    public function markAsPaid(): self
    {
        $this->status_id = DomainInvoiceStatus::PAID;
        $this->save();
        return $this;
    }

    /**
     * Marca como vencida.
     */
    public function markAsOverdue(): self
    {
        $this->status_id = DomainInvoiceStatus::OVERDUE;
        $this->save();
        return $this;
    }

    /**
     * Marca como cancelada.
     */
    public function markAsCanceled(): self
    {
        $this->status_id = DomainInvoiceStatus::CANCELED;
        $this->save();
        return $this;
    }

    /**
     * Calcula o total com juros e multas.
     */
    public function getTotalWithFeesAttribute(): float
    {
        if (!$this->isOverdue() || !$this->due_date) {
            return (float) $this->total_amount;
        }

        $daysOverdue = now()->diffInDays($this->due_date, false);
        if ($daysOverdue >= 0) {
            return (float) $this->total_amount;
        }

        $daysOverdue = abs($daysOverdue);
        $lateFeeDaily = config('financeiro.payment.late_fee_daily', 0.033);
        $latePenalty = config('financeiro.payment.late_penalty', 2.0);

        $interest = $this->total_amount * ($lateFeeDaily / 100) * $daysOverdue;
        $penalty = $this->total_amount * ($latePenalty / 100);

        return (float) $this->total_amount + $interest + $penalty;
    }

    /**
     * Recalcula o total com base nos itens.
     */
    public function recalculateTotal(): self
    {
        $itemsTotal = $this->items()->sum('amount');
        $discount = (float) ($this->discount_amount ?? 0);
        
        $this->total_amount = max(0, $itemsTotal - $discount);
        $this->save();

        return $this;
    }

    /**
     * Scope para faturas abertas.
     */
    public function scopeOpen($query)
    {
        return $query->where('status_id', DomainInvoiceStatus::OPEN);
    }

    /**
     * Scope para faturas pagas.
     */
    public function scopePaid($query)
    {
        return $query->where('status_id', DomainInvoiceStatus::PAID);
    }

    /**
     * Scope para faturas vencidas.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status_id', DomainInvoiceStatus::OVERDUE);
    }

    /**
     * Scope para faturas de um cliente.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope para faturas por período.
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope para faturas vencendo em X dias.
     */
    public function scopeDueSoon($query, int $days = 3)
    {
        return $query->where('status_id', DomainInvoiceStatus::OPEN)
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    /**
     * Configurações de log de atividade.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status_id', 'total_amount', 'due_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
