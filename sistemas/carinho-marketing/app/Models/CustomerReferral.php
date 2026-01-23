<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Model para indicações de clientes satisfeitos.
 * 
 * Gerencia o programa de indicação onde clientes ativos
 * podem indicar novos clientes e receber benefícios.
 */
class CustomerReferral extends Model
{
    protected $table = 'customer_referrals';

    protected $fillable = [
        'referrer_customer_id',
        'referrer_name',
        'referrer_phone',
        'referred_lead_id',
        'referred_name',
        'referred_phone',
        'referral_code',
        'converted',
        'contract_value',
        'benefit_type',
        'benefit_value',
        'benefit_applied',
        'referred_at',
        'converted_at',
    ];

    protected $casts = [
        'converted' => 'boolean',
        'contract_value' => 'decimal:2',
        'benefit_value' => 'decimal:2',
        'benefit_applied' => 'boolean',
        'referred_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    /**
     * Boot do model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->referral_code)) {
                $model->referral_code = self::generateReferralCode();
            }
        });
    }

    /**
     * Gera código de indicação único para cliente.
     */
    public static function generateReferralCode(): string
    {
        do {
            $code = 'C' . strtoupper(Str::random(7));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Encontra indicação pelo código.
     */
    public static function findByReferralCode(string $code): ?self
    {
        return self::where('referral_code', $code)->first();
    }

    /**
     * Scope para indicações convertidas.
     */
    public function scopeConverted($query)
    {
        return $query->where('converted', true);
    }

    /**
     * Scope para indicações pendentes.
     */
    public function scopePending($query)
    {
        return $query->where('converted', false);
    }

    /**
     * Scope para benefícios pendentes.
     */
    public function scopeBenefitPending($query)
    {
        return $query->where('converted', true)
            ->where('benefit_applied', false);
    }

    /**
     * Scope por cliente referenciador.
     */
    public function scopeByReferrer($query, string $customerId)
    {
        return $query->where('referrer_customer_id', $customerId);
    }

    /**
     * Scope por período.
     */
    public function scopeInPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('referred_at', [$startDate, $endDate]);
    }

    /**
     * Cria código de indicação para um cliente.
     */
    public static function createForCustomer(
        string $customerId,
        string $customerName,
        ?string $customerPhone = null
    ): self {
        // Verifica se já existe código para este cliente
        $existing = self::where('referrer_customer_id', $customerId)
            ->whereNull('referred_lead_id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return self::create([
            'referrer_customer_id' => $customerId,
            'referrer_name' => $customerName,
            'referrer_phone' => $customerPhone,
        ]);
    }

    /**
     * Registra lead indicado.
     */
    public static function registerReferred(
        string $referralCode,
        string $leadId,
        ?string $leadName = null,
        ?string $leadPhone = null
    ): ?self {
        $referral = self::where('referral_code', $referralCode)
            ->whereNull('referred_lead_id')
            ->first();

        if (!$referral) {
            // Cria nova indicação com o código
            $original = self::findByReferralCode($referralCode);
            if (!$original) {
                return null;
            }

            $referral = self::create([
                'referrer_customer_id' => $original->referrer_customer_id,
                'referrer_name' => $original->referrer_name,
                'referrer_phone' => $original->referrer_phone,
                'referral_code' => self::generateReferralCode(),
            ]);
        }

        $referral->update([
            'referred_lead_id' => $leadId,
            'referred_name' => $leadName,
            'referred_phone' => $leadPhone,
            'referred_at' => now(),
        ]);

        return $referral->fresh();
    }

    /**
     * Marca como convertido.
     */
    public function markAsConverted(float $contractValue): self
    {
        $config = ReferralProgramConfig::getActive();
        
        $benefitValue = $config ? $config->referrer_benefit_value : 0;
        $benefitType = $config ? $config->benefit_type : 'discount';

        $this->update([
            'converted' => true,
            'contract_value' => $contractValue,
            'benefit_type' => $benefitType,
            'benefit_value' => $benefitValue,
            'converted_at' => now(),
        ]);

        return $this->fresh();
    }

    /**
     * Aplica benefício.
     */
    public function applyBenefit(): self
    {
        $this->update(['benefit_applied' => true]);
        return $this;
    }

    /**
     * Conta indicações por cliente.
     */
    public static function countByReferrer(string $customerId): int
    {
        return self::byReferrer($customerId)
            ->whereNotNull('referred_lead_id')
            ->count();
    }

    /**
     * Conta conversões por cliente.
     */
    public static function countConversionsByReferrer(string $customerId): int
    {
        return self::byReferrer($customerId)
            ->converted()
            ->count();
    }
}
