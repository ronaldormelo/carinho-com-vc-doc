<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'category_id',
        'key',
        'name',
        'description',
        'value',
        'value_type',
        'unit',
        'default_value',
        'validation_rules',
        'is_editable',
        'is_public',
        'display_order',
    ];

    protected $casts = [
        'is_editable' => 'boolean',
        'is_public' => 'boolean',
        'validation_rules' => 'array',
    ];

    // Tipos de valor
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_DECIMAL = 'decimal';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_JSON = 'json';

    // Chaves de configuração - Pagamento
    public const KEY_PAYMENT_ADVANCE_HOURS = 'payment_advance_hours';
    public const KEY_PAYMENT_GRACE_DAYS = 'payment_grace_days';
    public const KEY_PAYMENT_LATE_FEE_DAILY = 'payment_late_fee_daily';
    public const KEY_PAYMENT_LATE_PENALTY = 'payment_late_penalty';

    // Chaves de configuração - Cancelamento
    public const KEY_CANCEL_FREE_HOURS = 'cancellation_free_hours';
    public const KEY_CANCEL_PARTIAL_HOURS = 'cancellation_partial_hours';
    public const KEY_CANCEL_PARTIAL_PERCENT = 'cancellation_partial_percent';
    public const KEY_CANCEL_NO_REFUND_HOURS = 'cancellation_no_refund_hours';
    public const KEY_CANCEL_ADMIN_FEE = 'cancellation_admin_fee';

    // Chaves de configuração - Comissões
    public const KEY_COMMISSION_DEFAULT = 'commission_default';
    public const KEY_COMMISSION_HORISTA = 'commission_horista';
    public const KEY_COMMISSION_DIARIO = 'commission_diario';
    public const KEY_COMMISSION_MENSAL = 'commission_mensal';

    // Chaves de configuração - Precificação
    public const KEY_PRICING_MIN_HOURLY = 'pricing_min_hourly';
    public const KEY_PRICING_HORISTA_HOUR = 'pricing_horista_hour';
    public const KEY_PRICING_HORISTA_MIN_HOURS = 'pricing_horista_min_hours';
    public const KEY_PRICING_DIARIO_DAY = 'pricing_diario_day';
    public const KEY_PRICING_MENSAL_MONTH = 'pricing_mensal_month';
    public const KEY_PRICING_NIGHT_SURCHARGE = 'pricing_night_surcharge';
    public const KEY_PRICING_WEEKEND_SURCHARGE = 'pricing_weekend_surcharge';
    public const KEY_PRICING_HOLIDAY_SURCHARGE = 'pricing_holiday_surcharge';
    public const KEY_PRICING_MONTHLY_DISCOUNT = 'pricing_monthly_discount';

    // Chaves de configuração - Margem
    public const KEY_MARGIN_MINIMUM = 'margin_minimum';
    public const KEY_MARGIN_TARGET = 'margin_target';
    public const KEY_MARGIN_ALERT = 'margin_alert';

    // Chaves de configuração - Repasses
    public const KEY_PAYOUT_FREQUENCY = 'payout_frequency';
    public const KEY_PAYOUT_DAY = 'payout_day';
    public const KEY_PAYOUT_MINIMUM = 'payout_minimum';
    public const KEY_PAYOUT_RELEASE_DAYS = 'payout_release_days';
    public const KEY_PAYOUT_PIX_FEE = 'payout_pix_fee';

    // Chaves de configuração - Fiscal
    public const KEY_FISCAL_AUTO_ISSUE = 'fiscal_auto_issue';
    public const KEY_FISCAL_ISS_RATE = 'fiscal_iss_rate';

    // Chaves de configuração - Limites
    public const KEY_LIMIT_CREDIT_PF = 'limit_credit_pf';
    public const KEY_LIMIT_CREDIT_PJ = 'limit_credit_pj';
    public const KEY_LIMIT_BLOCK_DAYS = 'limit_block_days';
    public const KEY_LIMIT_MAX_OVERDUE = 'limit_max_overdue';

    // Chaves de configuração - Bônus
    public const KEY_BONUS_RATING_MIN = 'bonus_rating_min';
    public const KEY_BONUS_RATING_PERCENT = 'bonus_rating_percent';
    public const KEY_BONUS_TENURE_6M = 'bonus_tenure_6m';
    public const KEY_BONUS_TENURE_12M = 'bonus_tenure_12m';
    public const KEY_BONUS_TENURE_24M = 'bonus_tenure_24m';

    /**
     * Relacionamento com categoria.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SettingCategory::class, 'category_id');
    }

    /**
     * Histórico de alterações.
     */
    public function history(): HasMany
    {
        return $this->hasMany(SettingHistory::class, 'setting_id')
            ->orderBy('changed_at', 'desc');
    }

    /**
     * Obtém o valor convertido para o tipo correto.
     */
    public function getTypedValueAttribute()
    {
        return $this->castValue($this->value);
    }

    /**
     * Obtém o valor padrão convertido.
     */
    public function getTypedDefaultValueAttribute()
    {
        return $this->castValue($this->default_value);
    }

    /**
     * Converte valor para o tipo correto.
     */
    protected function castValue($value)
    {
        if ($value === null) {
            return null;
        }

        return match ($this->value_type) {
            self::TYPE_INTEGER => (int) $value,
            self::TYPE_DECIMAL => (float) $value,
            self::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_JSON => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Define o valor com validação.
     */
    public function setTypedValue($value): self
    {
        $this->value = match ($this->value_type) {
            self::TYPE_JSON => is_string($value) ? $value : json_encode($value),
            self::TYPE_BOOLEAN => $value ? '1' : '0',
            default => (string) $value,
        };

        return $this;
    }

    /**
     * Verifica se está usando valor padrão.
     */
    public function isUsingDefault(): bool
    {
        return $this->value === $this->default_value;
    }

    /**
     * Restaura para valor padrão.
     */
    public function restoreDefault(): self
    {
        $this->value = $this->default_value;
        $this->save();

        return $this;
    }

    /**
     * Busca configuração por chave.
     */
    public static function getByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }

    /**
     * Obtém valor de uma configuração por chave (com cache).
     */
    public static function getValue(string $key, $default = null)
    {
        $cacheKey = "setting:{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::getByKey($key);

            if (!$setting) {
                return $default;
            }

            return $setting->typed_value;
        });
    }

    /**
     * Define valor de uma configuração por chave.
     */
    public static function setValue(string $key, $value, ?string $changedBy = null, ?string $reason = null): bool
    {
        $setting = static::getByKey($key);

        if (!$setting || !$setting->is_editable) {
            return false;
        }

        $oldValue = $setting->value;
        $setting->setTypedValue($value);
        $setting->save();

        // Registra histórico
        SettingHistory::create([
            'setting_id' => $setting->id,
            'old_value' => $oldValue,
            'new_value' => $setting->value,
            'changed_by' => $changedBy,
            'change_reason' => $reason,
            'changed_at' => now(),
        ]);

        // Limpa cache
        Cache::forget("setting:{$key}");
        Cache::forget('settings:all');

        return true;
    }

    /**
     * Scope para configurações editáveis.
     */
    public function scopeEditable($query)
    {
        return $query->where('is_editable', true);
    }

    /**
     * Scope para configurações públicas.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope por categoria.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope ordenado.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}
