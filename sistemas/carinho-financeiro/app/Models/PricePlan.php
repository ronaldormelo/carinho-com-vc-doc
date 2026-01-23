<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricePlan extends Model
{
    public $timestamps = false;

    protected $table = 'price_plans';

    protected $fillable = [
        'name',
        'service_type_id',
        'base_price',
        'active',
        'description',
        'min_hours',
        'max_hours',
        'region_code',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'active' => 'boolean',
        'min_hours' => 'integer',
        'max_hours' => 'integer',
    ];

    /**
     * Relacionamento com tipo de serviço.
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(DomainServiceType::class, 'service_type_id');
    }

    /**
     * Regras de preço associadas.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(PriceRule::class, 'plan_id');
    }

    /**
     * Verifica se o plano está ativo.
     */
    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    /**
     * Ativa o plano.
     */
    public function activate(): self
    {
        $this->active = true;
        $this->save();
        return $this;
    }

    /**
     * Desativa o plano.
     */
    public function deactivate(): self
    {
        $this->active = false;
        $this->save();
        return $this;
    }

    /**
     * Calcula o preço para uma quantidade específica.
     */
    public function calculatePrice(float $qty, array $context = []): float
    {
        $baseTotal = $this->base_price * $qty;
        
        // Aplica regras de preço
        foreach ($this->rules as $rule) {
            if ($rule->appliesTo($context)) {
                $baseTotal = $rule->apply($baseTotal, $qty, $context);
            }
        }

        // Garante preço mínimo
        $minHourly = config('financeiro.pricing.minimum_hourly', 35);
        $minTotal = $minHourly * $qty;

        return max($baseTotal, $minTotal);
    }

    /**
     * Verifica se atende ao preço mínimo viável.
     */
    public function meetsMinimumViable(): bool
    {
        $minHourly = config('financeiro.pricing.minimum_hourly', 35);
        return $this->base_price >= $minHourly;
    }

    /**
     * Scope para planos ativos.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para planos por tipo de serviço.
     */
    public function scopeByServiceType($query, int $serviceTypeId)
    {
        return $query->where('service_type_id', $serviceTypeId);
    }

    /**
     * Scope para planos por região.
     */
    public function scopeByRegion($query, ?string $regionCode)
    {
        if (!$regionCode) {
            return $query->whereNull('region_code');
        }
        return $query->where('region_code', $regionCode);
    }
}
