<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payout extends Model
{
    use LogsActivity;

    protected $table = 'payouts';

    protected $fillable = [
        'caregiver_id',
        'period_start',
        'period_end',
        'status_id',
        'total_amount',
        'commission_total',
        'transfer_fee',
        'net_amount',
        'stripe_transfer_id',
        'stripe_payout_id',
        'bank_account_id',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_amount' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'transfer_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainPayoutStatus::class, 'status_id');
    }

    /**
     * Itens do repasse.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PayoutItem::class, 'payout_id');
    }

    /**
     * Conta bancária do repasse.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    /**
     * Verifica se está aberto.
     */
    public function isOpen(): bool
    {
        return $this->status_id === DomainPayoutStatus::OPEN;
    }

    /**
     * Verifica se foi pago.
     */
    public function isPaid(): bool
    {
        return $this->status_id === DomainPayoutStatus::PAID;
    }

    /**
     * Verifica se foi cancelado.
     */
    public function isCanceled(): bool
    {
        return $this->status_id === DomainPayoutStatus::CANCELED;
    }

    /**
     * Verifica se pode ser processado.
     */
    public function canBeProcessed(): bool
    {
        $minAmount = config('financeiro.payout.minimum_amount', 50);
        return $this->isOpen() && $this->total_amount >= $minAmount;
    }

    /**
     * Marca como pago.
     */
    public function markAsPaid(?string $stripeTransferId = null): self
    {
        $this->status_id = DomainPayoutStatus::PAID;
        $this->processed_at = now();
        
        if ($stripeTransferId) {
            $this->stripe_transfer_id = $stripeTransferId;
        }
        
        $this->save();
        return $this;
    }

    /**
     * Marca como cancelado.
     */
    public function markAsCanceled(?string $reason = null): self
    {
        $this->status_id = DomainPayoutStatus::CANCELED;
        
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Cancelamento: {$reason}";
        }
        
        $this->save();
        return $this;
    }

    /**
     * Recalcula totais com base nos itens.
     */
    public function recalculateTotals(): self
    {
        $this->total_amount = $this->items()->sum('amount');
        $this->commission_total = $this->items()->sum(\DB::raw('amount * commission_percent / 100'));
        
        $fee = config('financeiro.payout.pix_fee', 0);
        $this->transfer_fee = $fee;
        $this->net_amount = $this->total_amount - $fee;
        
        $this->save();
        return $this;
    }

    /**
     * Scope para repasses abertos.
     */
    public function scopeOpen($query)
    {
        return $query->where('status_id', DomainPayoutStatus::OPEN);
    }

    /**
     * Scope para repasses pagos.
     */
    public function scopePaid($query)
    {
        return $query->where('status_id', DomainPayoutStatus::PAID);
    }

    /**
     * Scope para repasses de um cuidador.
     */
    public function scopeForCaregiver($query, int $caregiverId)
    {
        return $query->where('caregiver_id', $caregiverId);
    }

    /**
     * Scope para repasses por período.
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate]);
    }

    /**
     * Scope para repasses prontos para processar.
     */
    public function scopeReadyToProcess($query)
    {
        $minAmount = config('financeiro.payout.minimum_amount', 50);
        
        return $query->where('status_id', DomainPayoutStatus::OPEN)
            ->where('total_amount', '>=', $minAmount);
    }

    /**
     * Configurações de log de atividade.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status_id', 'total_amount', 'net_amount', 'processed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
