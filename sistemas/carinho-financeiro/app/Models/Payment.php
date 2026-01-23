<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use LogsActivity;

    public $timestamps = false;

    protected $table = 'payments';

    protected $fillable = [
        'invoice_id',
        'method_id',
        'amount',
        'status_id',
        'paid_at',
        'external_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'pix_code',
        'pix_qrcode_url',
        'boleto_url',
        'boleto_barcode',
        'refund_amount',
        'refund_reason',
        'refunded_at',
        'metadata',
        'idempotency_key',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'created_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot do model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            $payment->created_at = $payment->created_at ?? now();
            $payment->idempotency_key = $payment->idempotency_key ?? self::generateIdempotencyKey();
        });
    }

    /**
     * Gera chave de idempotência única.
     */
    public static function generateIdempotencyKey(): string
    {
        return 'pay_' . bin2hex(random_bytes(16));
    }

    /**
     * Relacionamento com a fatura.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Relacionamento com método de pagamento.
     */
    public function method(): BelongsTo
    {
        return $this->belongsTo(DomainPaymentMethod::class, 'method_id');
    }

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainPaymentStatus::class, 'status_id');
    }

    /**
     * Verifica se está pendente.
     */
    public function isPending(): bool
    {
        return $this->status_id === DomainPaymentStatus::PENDING;
    }

    /**
     * Verifica se foi pago.
     */
    public function isPaid(): bool
    {
        return $this->status_id === DomainPaymentStatus::PAID;
    }

    /**
     * Verifica se falhou.
     */
    public function isFailed(): bool
    {
        return $this->status_id === DomainPaymentStatus::FAILED;
    }

    /**
     * Verifica se foi reembolsado.
     */
    public function isRefunded(): bool
    {
        return $this->status_id === DomainPaymentStatus::REFUNDED;
    }

    /**
     * Verifica se é PIX.
     */
    public function isPix(): bool
    {
        return $this->method_id === DomainPaymentMethod::PIX;
    }

    /**
     * Verifica se é boleto.
     */
    public function isBoleto(): bool
    {
        return $this->method_id === DomainPaymentMethod::BOLETO;
    }

    /**
     * Verifica se é cartão.
     */
    public function isCard(): bool
    {
        return $this->method_id === DomainPaymentMethod::CARD;
    }

    /**
     * Marca como pago.
     */
    public function markAsPaid(?string $externalId = null): self
    {
        $this->status_id = DomainPaymentStatus::PAID;
        $this->paid_at = now();
        
        if ($externalId) {
            $this->external_id = $externalId;
        }
        
        $this->save();
        return $this;
    }

    /**
     * Marca como falhou.
     */
    public function markAsFailed(?string $reason = null): self
    {
        $this->status_id = DomainPaymentStatus::FAILED;
        
        if ($reason) {
            $metadata = $this->metadata ?? [];
            $metadata['failure_reason'] = $reason;
            $this->metadata = $metadata;
        }
        
        $this->save();
        return $this;
    }

    /**
     * Marca como reembolsado.
     */
    public function markAsRefunded(float $amount, ?string $reason = null): self
    {
        $this->status_id = DomainPaymentStatus::REFUNDED;
        $this->refund_amount = $amount;
        $this->refund_reason = $reason;
        $this->refunded_at = now();
        $this->save();
        return $this;
    }

    /**
     * Verifica se pode ser reembolsado.
     */
    public function canBeRefunded(): bool
    {
        return $this->isPaid() && !$this->isRefunded();
    }

    /**
     * Valor disponível para reembolso.
     */
    public function getRefundableAmountAttribute(): float
    {
        if (!$this->canBeRefunded()) {
            return 0;
        }

        $alreadyRefunded = (float) ($this->refund_amount ?? 0);
        return max(0, $this->amount - $alreadyRefunded);
    }

    /**
     * Scope para pagamentos pendentes.
     */
    public function scopePending($query)
    {
        return $query->where('status_id', DomainPaymentStatus::PENDING);
    }

    /**
     * Scope para pagamentos confirmados.
     */
    public function scopePaid($query)
    {
        return $query->where('status_id', DomainPaymentStatus::PAID);
    }

    /**
     * Scope para pagamentos por método.
     */
    public function scopeByMethod($query, int $methodId)
    {
        return $query->where('method_id', $methodId);
    }

    /**
     * Scope para pagamentos por período.
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('paid_at', [$startDate, $endDate]);
    }

    /**
     * Configurações de log de atividade.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status_id', 'amount', 'paid_at', 'external_id', 'refund_amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
