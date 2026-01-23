<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model para indicações de parcerias.
 * 
 * Registra cada lead indicado por um parceiro,
 * acompanhando conversão e comissão.
 */
class PartnershipReferral extends Model
{
    protected $table = 'partnership_referrals';

    protected $fillable = [
        'partnership_id',
        'lead_id',
        'lead_name',
        'lead_phone',
        'converted',
        'contract_value',
        'commission_value',
        'commission_paid',
        'referred_at',
        'converted_at',
    ];

    protected $casts = [
        'converted' => 'boolean',
        'contract_value' => 'decimal:2',
        'commission_value' => 'decimal:2',
        'commission_paid' => 'boolean',
        'referred_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    /**
     * Relacionamento com parceria.
     */
    public function partnership(): BelongsTo
    {
        return $this->belongsTo(MarketingPartnership::class, 'partnership_id');
    }

    /**
     * Scope para indicações convertidas.
     */
    public function scopeConverted($query)
    {
        return $query->where('converted', true);
    }

    /**
     * Scope para indicações não convertidas.
     */
    public function scopePending($query)
    {
        return $query->where('converted', false);
    }

    /**
     * Scope para comissões pendentes.
     */
    public function scopeCommissionPending($query)
    {
        return $query->where('converted', true)
            ->where('commission_paid', false);
    }

    /**
     * Scope por período.
     */
    public function scopeInPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('referred_at', [$startDate, $endDate]);
    }

    /**
     * Registra uma nova indicação.
     */
    public static function registerReferral(
        int $partnershipId,
        string $leadId,
        ?string $leadName = null,
        ?string $leadPhone = null
    ): self {
        return self::create([
            'partnership_id' => $partnershipId,
            'lead_id' => $leadId,
            'lead_name' => $leadName,
            'lead_phone' => $leadPhone,
            'converted' => false,
            'referred_at' => now(),
        ]);
    }

    /**
     * Marca como convertido.
     */
    public function markAsConverted(float $contractValue, ?float $commissionValue = null): self
    {
        // Calcula comissão se não fornecida
        if ($commissionValue === null) {
            $partnership = $this->partnership;
            if ($partnership && $partnership->commission_percent) {
                $commissionValue = $contractValue * ($partnership->commission_percent / 100);
            } else {
                $commissionValue = 0;
            }
        }

        $this->update([
            'converted' => true,
            'contract_value' => $contractValue,
            'commission_value' => $commissionValue,
            'converted_at' => now(),
        ]);

        return $this->fresh();
    }

    /**
     * Marca comissão como paga.
     */
    public function markCommissionPaid(): self
    {
        $this->update(['commission_paid' => true]);
        return $this;
    }
}
