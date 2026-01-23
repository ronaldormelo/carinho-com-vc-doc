<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BillingAccount extends Model
{
    use LogsActivity;

    protected $table = 'billing_accounts';

    protected $fillable = [
        'client_id',
        'payment_method_id',
        'status_id',
        'stripe_customer_id',
        'default_payment_method_stripe_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com método de pagamento.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(DomainPaymentMethod::class, 'payment_method_id');
    }

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainAccountStatus::class, 'status_id');
    }

    /**
     * Faturas desta conta.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'client_id', 'client_id');
    }

    /**
     * Verifica se a conta está ativa.
     */
    public function isActive(): bool
    {
        return $this->status_id === DomainAccountStatus::ACTIVE;
    }

    /**
     * Ativa a conta.
     */
    public function activate(): self
    {
        $this->status_id = DomainAccountStatus::ACTIVE;
        $this->save();
        return $this;
    }

    /**
     * Desativa a conta.
     */
    public function deactivate(): self
    {
        $this->status_id = DomainAccountStatus::INACTIVE;
        $this->save();
        return $this;
    }

    /**
     * Scope para contas ativas.
     */
    public function scopeActive($query)
    {
        return $query->where('status_id', DomainAccountStatus::ACTIVE);
    }

    /**
     * Verifica se tem cliente Stripe configurado.
     */
    public function hasStripeCustomer(): bool
    {
        return !empty($this->stripe_customer_id);
    }

    /**
     * Configurações de log de atividade.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['payment_method_id', 'status_id', 'stripe_customer_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
