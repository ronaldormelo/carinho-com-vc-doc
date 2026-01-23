<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SettingCategory;
use App\Models\SettingHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Gestão de Configurações.
 *
 * Centraliza o acesso às configurações do sistema financeiro,
 * com cache para performance e histórico de alterações.
 */
class SettingService
{
    /**
     * TTL do cache em segundos (1 hora).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Prefixo das chaves de cache.
     */
    protected const CACHE_PREFIX = 'fin_setting:';

    /*
    |--------------------------------------------------------------------------
    | Métodos de Leitura de Configurações
    |--------------------------------------------------------------------------
    */

    /**
     * Obtém valor de uma configuração.
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = Setting::getByKey($key);

            if (!$setting) {
                return $default;
            }

            return $setting->typed_value;
        });
    }

    /**
     * Obtém múltiplas configurações de uma vez.
     */
    public function getMany(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    /**
     * Obtém todas as configurações de uma categoria.
     */
    public function getByCategory(string $categoryCode): array
    {
        $cacheKey = self::CACHE_PREFIX . "category:{$categoryCode}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($categoryCode) {
            $category = SettingCategory::getByCode($categoryCode);

            if (!$category) {
                return [];
            }

            return $category->settings
                ->mapWithKeys(fn ($s) => [$s->key => $s->typed_value])
                ->toArray();
        });
    }

    /**
     * Obtém todas as configurações agrupadas por categoria.
     */
    public function getAll(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'all';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return SettingCategory::with(['settings' => fn ($q) => $q->ordered()])
                ->ordered()
                ->get()
                ->mapWithKeys(function ($category) {
                    return [
                        $category->code => [
                            'name' => $category->name,
                            'description' => $category->description,
                            'settings' => $category->settings->map(function ($setting) {
                                return [
                                    'key' => $setting->key,
                                    'name' => $setting->name,
                                    'description' => $setting->description,
                                    'value' => $setting->typed_value,
                                    'default_value' => $setting->typed_default_value,
                                    'value_type' => $setting->value_type,
                                    'unit' => $setting->unit,
                                    'is_editable' => $setting->is_editable,
                                    'is_public' => $setting->is_public,
                                ];
                            })->toArray(),
                        ],
                    ];
                })
                ->toArray();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos de Configurações Específicas
    |--------------------------------------------------------------------------
    */

    /**
     * Obtém configurações de pagamento.
     */
    public function getPaymentConfig(): array
    {
        return [
            'type' => 'prepaid',
            'advance_hours' => $this->get(Setting::KEY_PAYMENT_ADVANCE_HOURS, 24),
            'grace_period_days' => $this->get(Setting::KEY_PAYMENT_GRACE_DAYS, 0),
            'late_fee_daily' => $this->get(Setting::KEY_PAYMENT_LATE_FEE_DAILY, 0.033),
            'late_penalty' => $this->get(Setting::KEY_PAYMENT_LATE_PENALTY, 2.0),
        ];
    }

    /**
     * Obtém configurações de cancelamento.
     */
    public function getCancellationConfig(): array
    {
        return [
            'free_cancellation_hours' => $this->get(Setting::KEY_CANCEL_FREE_HOURS, 24),
            'partial_refund' => [
                'hours_before' => $this->get(Setting::KEY_CANCEL_PARTIAL_HOURS, 12),
                'refund_percent' => $this->get(Setting::KEY_CANCEL_PARTIAL_PERCENT, 50),
            ],
            'no_refund_hours' => $this->get(Setting::KEY_CANCEL_NO_REFUND_HOURS, 6),
            'admin_fee_percent' => $this->get(Setting::KEY_CANCEL_ADMIN_FEE, 5),
            'caregiver_cancel_full_refund' => true,
        ];
    }

    /**
     * Obtém configurações de comissão.
     */
    public function getCommissionConfig(): array
    {
        $defaultPercent = $this->get(Setting::KEY_COMMISSION_DEFAULT, 70);

        return [
            'caregiver_percent' => $defaultPercent,
            'company_percent' => 100 - $defaultPercent,
            'by_service_type' => [
                'horista' => [
                    'caregiver_percent' => $this->get(Setting::KEY_COMMISSION_HORISTA, 70),
                ],
                'diario' => [
                    'caregiver_percent' => $this->get(Setting::KEY_COMMISSION_DIARIO, 72),
                ],
                'mensal' => [
                    'caregiver_percent' => $this->get(Setting::KEY_COMMISSION_MENSAL, 75),
                ],
            ],
            'rating_bonus' => [
                'min_rating' => $this->get(Setting::KEY_BONUS_RATING_MIN, 4.5),
                'bonus_percent' => $this->get(Setting::KEY_BONUS_RATING_PERCENT, 2.0),
            ],
            'tenure_bonus' => [
                '6_months' => $this->get(Setting::KEY_BONUS_TENURE_6M, 1.0),
                '12_months' => $this->get(Setting::KEY_BONUS_TENURE_12M, 2.0),
                '24_months' => $this->get(Setting::KEY_BONUS_TENURE_24M, 3.0),
            ],
        ];
    }

    /**
     * Obtém comissão do cuidador por tipo de serviço.
     */
    public function getCaregiverCommission(string $serviceTypeCode): float
    {
        return match ($serviceTypeCode) {
            'horista' => $this->get(Setting::KEY_COMMISSION_HORISTA, 70),
            'diario' => $this->get(Setting::KEY_COMMISSION_DIARIO, 72),
            'mensal' => $this->get(Setting::KEY_COMMISSION_MENSAL, 75),
            default => $this->get(Setting::KEY_COMMISSION_DEFAULT, 70),
        };
    }

    /**
     * Obtém configurações de precificação.
     */
    public function getPricingConfig(): array
    {
        return [
            'minimum_hourly' => $this->get(Setting::KEY_PRICING_MIN_HOURLY, 35.00),
            'base' => [
                'horista' => [
                    'price_per_hour' => $this->get(Setting::KEY_PRICING_HORISTA_HOUR, 50.00),
                    'minimum_hours' => $this->get(Setting::KEY_PRICING_HORISTA_MIN_HOURS, 4),
                ],
                'diario' => [
                    'price_per_day' => $this->get(Setting::KEY_PRICING_DIARIO_DAY, 300.00),
                    'hours_per_day' => 12,
                ],
                'mensal' => [
                    'price_per_month' => $this->get(Setting::KEY_PRICING_MENSAL_MONTH, 6000.00),
                    'days_per_week' => 5,
                    'hours_per_day' => 8,
                ],
            ],
            'night_surcharge' => $this->get(Setting::KEY_PRICING_NIGHT_SURCHARGE, 20),
            'weekend_surcharge' => $this->get(Setting::KEY_PRICING_WEEKEND_SURCHARGE, 30),
            'holiday_surcharge' => $this->get(Setting::KEY_PRICING_HOLIDAY_SURCHARGE, 50),
            'monthly_discount' => $this->get(Setting::KEY_PRICING_MONTHLY_DISCOUNT, 10),
        ];
    }

    /**
     * Obtém configurações de margem.
     */
    public function getMarginConfig(): array
    {
        return [
            'minimum' => $this->get(Setting::KEY_MARGIN_MINIMUM, 25),
            'target' => $this->get(Setting::KEY_MARGIN_TARGET, 30),
            'alert_threshold' => $this->get(Setting::KEY_MARGIN_ALERT, 20),
        ];
    }

    /**
     * Obtém configurações de repasse.
     */
    public function getPayoutConfig(): array
    {
        return [
            'frequency' => $this->get(Setting::KEY_PAYOUT_FREQUENCY, 'weekly'),
            'day_of_week' => $this->get(Setting::KEY_PAYOUT_DAY, 5),
            'minimum_amount' => $this->get(Setting::KEY_PAYOUT_MINIMUM, 50.00),
            'release_days' => $this->get(Setting::KEY_PAYOUT_RELEASE_DAYS, 3),
            'pix_fee' => $this->get(Setting::KEY_PAYOUT_PIX_FEE, 0.00),
        ];
    }

    /**
     * Obtém configurações fiscais.
     */
    public function getFiscalConfig(): array
    {
        return [
            'auto_issue' => $this->get(Setting::KEY_FISCAL_AUTO_ISSUE, true),
            'iss_rate' => $this->get(Setting::KEY_FISCAL_ISS_RATE, 5.0),
        ];
    }

    /**
     * Obtém configurações de limites.
     */
    public function getLimitsConfig(): array
    {
        return [
            'initial_credit_pf' => $this->get(Setting::KEY_LIMIT_CREDIT_PF, 0),
            'initial_credit_pj' => $this->get(Setting::KEY_LIMIT_CREDIT_PJ, 0),
            'block_after_days' => $this->get(Setting::KEY_LIMIT_BLOCK_DAYS, 7),
            'max_overdue_amount' => $this->get(Setting::KEY_LIMIT_MAX_OVERDUE, 500.00),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos de Escrita de Configurações
    |--------------------------------------------------------------------------
    */

    /**
     * Define valor de uma configuração.
     */
    public function set(string $key, $value, ?string $changedBy = null, ?string $reason = null): bool
    {
        $setting = Setting::getByKey($key);

        if (!$setting) {
            Log::warning('Configuração não encontrada', ['key' => $key]);
            return false;
        }

        if (!$setting->is_editable) {
            Log::warning('Configuração não é editável', ['key' => $key]);
            return false;
        }

        // Valida o valor
        if (!$this->validateValue($setting, $value)) {
            Log::warning('Valor inválido para configuração', ['key' => $key, 'value' => $value]);
            return false;
        }

        return DB::transaction(function () use ($setting, $value, $changedBy, $reason, $key) {
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
            $this->clearCache($key);

            Log::info('Configuração alterada', [
                'key' => $key,
                'old_value' => $oldValue,
                'new_value' => $setting->value,
                'changed_by' => $changedBy,
            ]);

            return true;
        });
    }

    /**
     * Define múltiplas configurações de uma vez.
     */
    public function setMany(array $settings, ?string $changedBy = null, ?string $reason = null): array
    {
        $results = [];

        foreach ($settings as $key => $value) {
            $results[$key] = $this->set($key, $value, $changedBy, $reason);
        }

        return $results;
    }

    /**
     * Restaura configuração para valor padrão.
     */
    public function restoreDefault(string $key, ?string $changedBy = null): bool
    {
        $setting = Setting::getByKey($key);

        if (!$setting || !$setting->is_editable) {
            return false;
        }

        return $this->set($key, $setting->default_value, $changedBy, 'Restaurado para valor padrão');
    }

    /**
     * Restaura todas as configurações de uma categoria para valores padrão.
     */
    public function restoreCategoryDefaults(string $categoryCode, ?string $changedBy = null): int
    {
        $category = SettingCategory::getByCode($categoryCode);

        if (!$category) {
            return 0;
        }

        $count = 0;

        foreach ($category->settings()->editable()->get() as $setting) {
            if ($this->restoreDefault($setting->key, $changedBy)) {
                $count++;
            }
        }

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos de Validação
    |--------------------------------------------------------------------------
    */

    /**
     * Valida valor para uma configuração.
     */
    protected function validateValue(Setting $setting, $value): bool
    {
        // Validação por tipo
        $valid = match ($setting->value_type) {
            Setting::TYPE_INTEGER => is_numeric($value) && (int) $value == $value,
            Setting::TYPE_DECIMAL => is_numeric($value),
            Setting::TYPE_BOOLEAN => is_bool($value) || in_array($value, [0, 1, '0', '1', true, false], true),
            Setting::TYPE_JSON => is_array($value) || $this->isValidJson($value),
            default => true,
        };

        if (!$valid) {
            return false;
        }

        // Validação por regras customizadas
        if (!empty($setting->validation_rules)) {
            $rules = $setting->validation_rules;

            if (isset($rules['min']) && $value < $rules['min']) {
                return false;
            }

            if (isset($rules['max']) && $value > $rules['max']) {
                return false;
            }

            if (isset($rules['in']) && !in_array($value, $rules['in'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica se é JSON válido.
     */
    protected function isValidJson($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos de Cache
    |--------------------------------------------------------------------------
    */

    /**
     * Limpa cache de uma configuração.
     */
    public function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget(self::CACHE_PREFIX . $key);

            // Também limpa cache da categoria
            $setting = Setting::getByKey($key);
            if ($setting) {
                Cache::forget(self::CACHE_PREFIX . "category:{$setting->category->code}");
            }
        }

        // Sempre limpa cache geral
        Cache::forget(self::CACHE_PREFIX . 'all');
    }

    /**
     * Limpa todo o cache de configurações.
     */
    public function clearAllCache(): void
    {
        $settings = Setting::all();

        foreach ($settings as $setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        }

        $categories = SettingCategory::all();

        foreach ($categories as $category) {
            Cache::forget(self::CACHE_PREFIX . "category:{$category->code}");
        }

        Cache::forget(self::CACHE_PREFIX . 'all');
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos de Histórico
    |--------------------------------------------------------------------------
    */

    /**
     * Obtém histórico de uma configuração.
     */
    public function getHistory(string $key, int $limit = 20): Collection
    {
        $setting = Setting::getByKey($key);

        if (!$setting) {
            return collect();
        }

        return $setting->history()->take($limit)->get();
    }

    /**
     * Obtém histórico de todas as alterações.
     */
    public function getAllHistory(int $limit = 50): Collection
    {
        return SettingHistory::with(['setting.category'])
            ->orderBy('changed_at', 'desc')
            ->take($limit)
            ->get();
    }
}
