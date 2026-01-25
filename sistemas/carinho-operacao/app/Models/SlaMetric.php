<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Métricas de SLA operacional.
 * 
 * Armazena indicadores diários de performance para acompanhamento
 * e análise de tendências conforme práticas de gestão.
 *
 * @property int $id
 * @property string $metric_date
 * @property string $metric_type
 * @property ?string $dimension
 * @property ?string $dimension_value
 * @property float $target_value
 * @property float $actual_value
 * @property bool $target_met
 * @property int $sample_size
 * @property string $created_at
 * @property string $updated_at
 */
class SlaMetric extends Model
{
    protected $table = 'sla_metrics';

    protected $fillable = [
        'metric_date',
        'metric_type',
        'dimension',
        'dimension_value',
        'target_value',
        'actual_value',
        'target_met',
        'sample_size',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'target_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'target_met' => 'boolean',
    ];

    // Tipos de métrica
    const TYPE_ALLOCATION_TIME = 'allocation_time';
    const TYPE_CHECKIN_PUNCTUALITY = 'checkin_punctuality';
    const TYPE_CHECKOUT_PUNCTUALITY = 'checkout_punctuality';
    const TYPE_SUBSTITUTION_RATE = 'substitution_rate';
    const TYPE_CANCELLATION_RATE = 'cancellation_rate';
    const TYPE_EMERGENCY_RESPONSE = 'emergency_response';
    const TYPE_NOTIFICATION_SUCCESS = 'notification_success';
    const TYPE_OCCUPANCY_RATE = 'occupancy_rate';

    // Dimensões
    const DIMENSION_GLOBAL = null;
    const DIMENSION_REGION = 'region';
    const DIMENSION_CAREGIVER = 'caregiver';
    const DIMENSION_SERVICE_TYPE = 'service_type';

    /**
     * Alertas relacionados.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(SlaAlert::class, 'sla_metric_id');
    }

    /**
     * Calcula variação percentual em relação ao target.
     */
    public function getVariancePercentAttribute(): float
    {
        if ($this->target_value == 0) {
            return 0;
        }

        return round((($this->actual_value - $this->target_value) / $this->target_value) * 100, 2);
    }

    /**
     * Retorna status formatado.
     */
    public function getStatusAttribute(): string
    {
        return $this->target_met ? 'Dentro do SLA' : 'Fora do SLA';
    }

    /**
     * Scope por tipo de métrica.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('metric_type', $type);
    }

    /**
     * Scope por período.
     */
    public function scopeInPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    /**
     * Scope para métricas fora do SLA.
     */
    public function scopeOutOfSla($query)
    {
        return $query->where('target_met', false);
    }

    /**
     * Scope por dimensão.
     */
    public function scopeByDimension($query, ?string $dimension, ?string $value = null)
    {
        if ($dimension === null) {
            return $query->whereNull('dimension');
        }

        $query->where('dimension', $dimension);

        if ($value !== null) {
            $query->where('dimension_value', $value);
        }

        return $query;
    }

    /**
     * Tipos disponíveis com labels.
     */
    public static function availableTypes(): array
    {
        return [
            self::TYPE_ALLOCATION_TIME => 'Tempo de Alocação',
            self::TYPE_CHECKIN_PUNCTUALITY => 'Pontualidade Check-in',
            self::TYPE_CHECKOUT_PUNCTUALITY => 'Pontualidade Check-out',
            self::TYPE_SUBSTITUTION_RATE => 'Taxa de Substituição',
            self::TYPE_CANCELLATION_RATE => 'Taxa de Cancelamento',
            self::TYPE_EMERGENCY_RESPONSE => 'Tempo de Resposta Emergência',
            self::TYPE_NOTIFICATION_SUCCESS => 'Sucesso de Notificações',
            self::TYPE_OCCUPANCY_RATE => 'Taxa de Ocupação',
        ];
    }
}
