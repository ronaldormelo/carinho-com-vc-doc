<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceRule extends Model
{
    public $timestamps = false;

    protected $table = 'price_rules';

    protected $fillable = [
        'plan_id',
        'rule_type',
        'value',
        'conditions_json',
        'name',
        'priority',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'conditions_json' => 'array',
        'priority' => 'integer',
    ];

    /**
     * Tipos de regra disponíveis.
     */
    public const TYPE_NIGHT_SURCHARGE = 'night_surcharge';
    public const TYPE_WEEKEND_SURCHARGE = 'weekend_surcharge';
    public const TYPE_HOLIDAY_SURCHARGE = 'holiday_surcharge';
    public const TYPE_MONTHLY_DISCOUNT = 'monthly_discount';
    public const TYPE_QUANTITY_DISCOUNT = 'quantity_discount';
    public const TYPE_LOYALTY_DISCOUNT = 'loyalty_discount';
    public const TYPE_PERCENTAGE_ADJUSTMENT = 'percentage_adjustment';
    public const TYPE_FIXED_ADJUSTMENT = 'fixed_adjustment';

    /**
     * Relacionamento com o plano.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(PricePlan::class, 'plan_id');
    }

    /**
     * Verifica se a regra se aplica ao contexto.
     */
    public function appliesTo(array $context): bool
    {
        if (empty($this->conditions_json)) {
            return true;
        }

        foreach ($this->conditions_json as $condition => $expectedValue) {
            $actualValue = $context[$condition] ?? null;

            if ($actualValue === null) {
                return false;
            }

            // Condições especiais
            if (is_array($expectedValue)) {
                if (isset($expectedValue['min']) && $actualValue < $expectedValue['min']) {
                    return false;
                }
                if (isset($expectedValue['max']) && $actualValue > $expectedValue['max']) {
                    return false;
                }
                if (isset($expectedValue['in']) && !in_array($actualValue, $expectedValue['in'])) {
                    return false;
                }
            } elseif ($actualValue !== $expectedValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * Aplica a regra ao valor.
     */
    public function apply(float $baseAmount, float $qty, array $context = []): float
    {
        switch ($this->rule_type) {
            case self::TYPE_PERCENTAGE_ADJUSTMENT:
            case self::TYPE_NIGHT_SURCHARGE:
            case self::TYPE_WEEKEND_SURCHARGE:
            case self::TYPE_HOLIDAY_SURCHARGE:
                // Acréscimo percentual
                return $baseAmount * (1 + ($this->value / 100));

            case self::TYPE_MONTHLY_DISCOUNT:
            case self::TYPE_QUANTITY_DISCOUNT:
            case self::TYPE_LOYALTY_DISCOUNT:
                // Desconto percentual
                return $baseAmount * (1 - ($this->value / 100));

            case self::TYPE_FIXED_ADJUSTMENT:
                // Ajuste fixo (positivo = acréscimo, negativo = desconto)
                return $baseAmount + $this->value;

            default:
                return $baseAmount;
        }
    }

    /**
     * Scope ordenado por prioridade.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }
}
