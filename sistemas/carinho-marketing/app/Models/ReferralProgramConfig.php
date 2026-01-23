<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model para configuração do programa de indicação.
 * 
 * Define as regras e benefícios do programa de indicação
 * de clientes satisfeitos.
 */
class ReferralProgramConfig extends Model
{
    protected $table = 'referral_program_config';

    protected $fillable = [
        'is_active',
        'benefit_type',
        'referrer_benefit_value',
        'referred_benefit_value',
        'min_contract_value',
        'max_referrals_per_month',
        'terms',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'referrer_benefit_value' => 'decimal:2',
        'referred_benefit_value' => 'decimal:2',
    ];

    // Tipos de benefício
    public const BENEFIT_DISCOUNT = 'discount';
    public const BENEFIT_BONUS = 'bonus';
    public const BENEFIT_GIFT = 'gift';

    /**
     * Obtém configuração ativa.
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Verifica se o programa está ativo.
     */
    public static function isProgramActive(): bool
    {
        $config = self::getActive();
        return $config && $config->is_active;
    }

    /**
     * Obtém valor do benefício para quem indica.
     */
    public static function getReferrerBenefit(): float
    {
        $config = self::getActive();
        return $config ? $config->referrer_benefit_value : 0;
    }

    /**
     * Obtém tipo de benefício.
     */
    public static function getBenefitType(): string
    {
        $config = self::getActive();
        return $config ? $config->benefit_type : self::BENEFIT_DISCOUNT;
    }

    /**
     * Verifica se cliente pode indicar mais neste mês.
     */
    public static function canReferMore(string $customerId): bool
    {
        $config = self::getActive();
        if (!$config || !$config->is_active) {
            return false;
        }

        $thisMonth = CustomerReferral::byReferrer($customerId)
            ->whereMonth('referred_at', now()->month)
            ->whereYear('referred_at', now()->year)
            ->count();

        return $thisMonth < $config->max_referrals_per_month;
    }

    /**
     * Verifica se valor do contrato atende ao mínimo.
     */
    public static function meetsMinimumValue(float $contractValue): bool
    {
        $config = self::getActive();
        if (!$config) {
            return true;
        }

        return $contractValue >= $config->min_contract_value;
    }
}
