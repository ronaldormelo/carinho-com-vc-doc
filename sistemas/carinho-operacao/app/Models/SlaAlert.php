<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alertas de SLA.
 * 
 * Registra violações e tendências preocupantes de indicadores
 * para ação proativa da gestão operacional.
 *
 * @property int $id
 * @property ?int $sla_metric_id
 * @property string $alert_type
 * @property string $metric_type
 * @property string $message
 * @property string $severity
 * @property bool $is_acknowledged
 * @property ?int $acknowledged_by
 * @property ?string $acknowledged_at
 * @property string $created_at
 * @property string $updated_at
 */
class SlaAlert extends Model
{
    protected $table = 'sla_alerts';

    protected $fillable = [
        'sla_metric_id',
        'alert_type',
        'metric_type',
        'message',
        'severity',
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
    ];

    protected $casts = [
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    // Tipos de alerta
    const TYPE_THRESHOLD_BREACH = 'threshold_breach';
    const TYPE_TREND_WARNING = 'trend_warning';
    const TYPE_CRITICAL = 'critical';

    // Severidades
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Métrica relacionada.
     */
    public function metric(): BelongsTo
    {
        return $this->belongsTo(SlaMetric::class, 'sla_metric_id');
    }

    /**
     * Confirma o alerta.
     */
    public function acknowledge(int $userId): self
    {
        $this->is_acknowledged = true;
        $this->acknowledged_by = $userId;
        $this->acknowledged_at = now();
        $this->save();

        return $this;
    }

    /**
     * Verifica se é crítico.
     */
    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    /**
     * Scope para não confirmados.
     */
    public function scopeUnacknowledged($query)
    {
        return $query->where('is_acknowledged', false);
    }

    /**
     * Scope por severidade.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope ordenado por prioridade.
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')");
    }

    /**
     * Labels de severidade.
     */
    public static function severityLabels(): array
    {
        return [
            self::SEVERITY_INFO => 'Informativo',
            self::SEVERITY_WARNING => 'Atenção',
            self::SEVERITY_CRITICAL => 'Crítico',
        ];
    }
}
