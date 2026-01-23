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
        'notes',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
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

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class, 'caregiver_id');
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

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->incident_type] ?? $this->incident_type;
    }
}
