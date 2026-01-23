<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverIncident extends Model
{
    protected $table = 'caregiver_incidents';

    public $timestamps = false;

    protected $fillable = [
        'caregiver_id',
        'service_id',
        'incident_type',
        'severity_id',
        'notes',
        'resolution_notes',
        'resolved_at',
        'resolved_by',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Tipos de incidente padrao
     */
    public const TYPES = [
        'atraso' => 'Atraso',
        'falta' => 'Falta sem aviso',
        'comportamento' => 'Comportamento inadequado',
        'qualidade' => 'Qualidade do servico',
        'reclamacao' => 'Reclamacao do cliente',
        'comunicacao' => 'Falha de comunicacao',
        'outro' => 'Outro',
    ];

    /**
     * Mapeamento de tipo para severidade sugerida
     */
    public const TYPE_SEVERITY_MAP = [
        'atraso' => DomainIncidentSeverity::LOW,
        'comunicacao' => DomainIncidentSeverity::LOW,
        'qualidade' => DomainIncidentSeverity::MEDIUM,
        'reclamacao' => DomainIncidentSeverity::MEDIUM,
        'falta' => DomainIncidentSeverity::HIGH,
        'comportamento' => DomainIncidentSeverity::HIGH,
        'outro' => DomainIncidentSeverity::MEDIUM,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class, 'caregiver_id');
    }

    public function severity(): BelongsTo
    {
        return $this->belongsTo(DomainIncidentSeverity::class, 'severity_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOfType($query, string $type)
    {
        return $query->where('incident_type', $type);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    public function scopeForService($query, int $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    public function scopeBySeverity($query, int $severityId)
    {
        return $query->where('severity_id', $severityId);
    }

    public function scopeSevere($query)
    {
        return $query->where('severity_id', '>=', DomainIncidentSeverity::HIGH);
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopePendingResolution($query)
    {
        return $query->whereNull('resolved_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->incident_type] ?? $this->incident_type;
    }

    public function getSeverityLabelAttribute(): string
    {
        return $this->severity?->label ?? 'Não definida';
    }

    public function getSeverityWeightAttribute(): int
    {
        return $this->severity?->weight ?? 1;
    }

    public function getIsResolvedAttribute(): bool
    {
        return !is_null($this->resolved_at);
    }

    public function getIsSevereAttribute(): bool
    {
        return $this->severity_id >= DomainIncidentSeverity::HIGH;
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Retorna a severidade sugerida para o tipo de incidente.
     */
    public static function getSuggestedSeverity(string $incidentType): int
    {
        return self::TYPE_SEVERITY_MAP[$incidentType] ?? DomainIncidentSeverity::MEDIUM;
    }

    /**
     * Resolve o incidente.
     */
    public function resolve(string $resolvedBy, ?string $notes = null): self
    {
        $this->update([
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
            'resolution_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Adiciona nota de resolução.
     */
    public function addResolutionNote(string $note): self
    {
        $existingNotes = $this->resolution_notes ?? '';
        $timestamp = now()->format('d/m/Y H:i');
        $newNote = $existingNotes ? $existingNotes . "\n\n" : '';
        $newNote .= "[{$timestamp}] {$note}";

        $this->update(['resolution_notes' => $newNote]);

        return $this;
    }
}
