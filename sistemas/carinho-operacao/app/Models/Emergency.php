<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de emergencia.
 *
 * @property int $id
 * @property int $service_request_id
 * @property int $severity_id
 * @property string $description
 * @property ?string $resolved_at
 */
class Emergency extends Model
{
    protected $table = 'emergencies';
    public $timestamps = false;

    protected $fillable = [
        'service_request_id',
        'severity_id',
        'description',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Solicitacao de servico associada.
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    /**
     * Severidade da emergencia.
     */
    public function severity(): BelongsTo
    {
        return $this->belongsTo(DomainEmergencySeverity::class, 'severity_id');
    }

    /**
     * Verifica se foi resolvida.
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Verifica se e critica.
     */
    public function isCritical(): bool
    {
        return $this->severity_id === DomainEmergencySeverity::CRITICAL;
    }

    /**
     * Verifica se e alta severidade.
     */
    public function isHigh(): bool
    {
        return $this->severity_id === DomainEmergencySeverity::HIGH;
    }

    /**
     * Marca como resolvida.
     */
    public function resolve(): self
    {
        $this->resolved_at = now();
        $this->save();

        return $this;
    }

    /**
     * Retorna tempo maximo de resposta em minutos.
     */
    public function getResponseTimeLimitAttribute(): int
    {
        $limits = config('operacao.emergency.response_time', []);
        $severity = $this->severity?->code ?? 'medium';

        return $limits[$severity] ?? 30;
    }

    /**
     * Scope para emergencias pendentes.
     */
    public function scopePending($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope para emergencias resolvidas.
     */
    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    /**
     * Scope por severidade.
     */
    public function scopeWithSeverity($query, int $severityId)
    {
        return $query->where('severity_id', $severityId);
    }

    /**
     * Scope para criticas.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity_id', DomainEmergencySeverity::CRITICAL);
    }
}
