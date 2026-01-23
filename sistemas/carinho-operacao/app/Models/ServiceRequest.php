<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Solicitacao de servico.
 *
 * @property int $id
 * @property int $client_id
 * @property int $service_type_id
 * @property int $urgency_id
 * @property ?string $start_date
 * @property ?string $end_date
 * @property int $status_id
 * @property string $created_at
 * @property ?string $updated_at
 */
class ServiceRequest extends Model
{
    protected $table = 'service_requests';

    protected $fillable = [
        'client_id',
        'service_type_id',
        'urgency_id',
        'start_date',
        'end_date',
        'status_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Tipo de servico.
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(DomainServiceType::class, 'service_type_id');
    }

    /**
     * Nivel de urgencia.
     */
    public function urgency(): BelongsTo
    {
        return $this->belongsTo(DomainUrgencyLevel::class, 'urgency_id');
    }

    /**
     * Status atual.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainServiceStatus::class, 'status_id');
    }

    /**
     * Alocacoes de cuidadores.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'service_request_id');
    }

    /**
     * Alocacao ativa.
     */
    public function activeAssignment(): HasOne
    {
        return $this->hasOne(Assignment::class, 'service_request_id')
            ->whereIn('status_id', [DomainAssignmentStatus::ASSIGNED, DomainAssignmentStatus::CONFIRMED]);
    }

    /**
     * Checklists associados.
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class, 'service_request_id');
    }

    /**
     * Emergencias relacionadas.
     */
    public function emergencies(): HasMany
    {
        return $this->hasMany(Emergency::class, 'service_request_id');
    }

    /**
     * Verifica se esta aberto.
     */
    public function isOpen(): bool
    {
        return $this->status_id === DomainServiceStatus::OPEN;
    }

    /**
     * Verifica se esta ativo.
     */
    public function isActive(): bool
    {
        return $this->status_id === DomainServiceStatus::ACTIVE;
    }

    /**
     * Verifica se foi cancelado.
     */
    public function isCanceled(): bool
    {
        return $this->status_id === DomainServiceStatus::CANCELED;
    }

    /**
     * Verifica se foi concluido.
     */
    public function isCompleted(): bool
    {
        return $this->status_id === DomainServiceStatus::COMPLETED;
    }

    /**
     * Scope para solicitacoes abertas.
     */
    public function scopeOpen($query)
    {
        return $query->where('status_id', DomainServiceStatus::OPEN);
    }

    /**
     * Scope para solicitacoes ativas.
     */
    public function scopeActive($query)
    {
        return $query->where('status_id', DomainServiceStatus::ACTIVE);
    }

    /**
     * Scope por cliente.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope para urgentes (hoje).
     */
    public function scopeUrgent($query)
    {
        return $query->where('urgency_id', DomainUrgencyLevel::HOJE);
    }
}
