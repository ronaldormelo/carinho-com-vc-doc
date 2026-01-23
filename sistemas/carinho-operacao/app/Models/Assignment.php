<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Alocacao de cuidador para uma solicitacao.
 *
 * @property int $id
 * @property int $service_request_id
 * @property int $caregiver_id
 * @property int $status_id
 * @property string $assigned_at
 */
class Assignment extends Model
{
    protected $table = 'assignments';
    public $timestamps = false;

    protected $fillable = [
        'service_request_id',
        'caregiver_id',
        'status_id',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * Solicitacao de servico.
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    /**
     * Status da alocacao.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainAssignmentStatus::class, 'status_id');
    }

    /**
     * Agendamentos associados.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'assignment_id');
    }

    /**
     * Substituicoes onde este foi o cuidador substituido.
     */
    public function substitutions(): HasMany
    {
        return $this->hasMany(Substitution::class, 'assignment_id');
    }

    /**
     * Verifica se esta confirmado.
     */
    public function isConfirmed(): bool
    {
        return $this->status_id === DomainAssignmentStatus::CONFIRMED;
    }

    /**
     * Verifica se foi substituido.
     */
    public function isReplaced(): bool
    {
        return $this->status_id === DomainAssignmentStatus::REPLACED;
    }

    /**
     * Verifica se foi cancelado.
     */
    public function isCanceled(): bool
    {
        return $this->status_id === DomainAssignmentStatus::CANCELED;
    }

    /**
     * Scope para alocacoes ativas (assigned ou confirmed).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status_id', [
            DomainAssignmentStatus::ASSIGNED,
            DomainAssignmentStatus::CONFIRMED,
        ]);
    }

    /**
     * Scope por cuidador.
     */
    public function scopeForCaregiver($query, int $caregiverId)
    {
        return $query->where('caregiver_id', $caregiverId);
    }
}
