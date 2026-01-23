<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de alocações/serviços realizados pelo cuidador.
 * Permite rastrear histórico de trabalho e calcular indicadores.
 */
class CaregiverAssignment extends Model
{
    protected $table = 'caregiver_assignments';

    protected $fillable = [
        'caregiver_id',
        'service_id',
        'client_id',
        'started_at',
        'ended_at',
        'hours_worked',
        'status',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'hours_worked' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Status possíveis da alocação
     */
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    public const STATUSES = [
        self::STATUS_SCHEDULED => 'Agendado',
        self::STATUS_IN_PROGRESS => 'Em Andamento',
        self::STATUS_COMPLETED => 'Concluído',
        self::STATUS_CANCELLED => 'Cancelado',
        self::STATUS_NO_SHOW => 'Não Compareceu',
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

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('started_at', '>=', $startDate)
            ->where('started_at', '<=', $endDate);
    }

    public function scopeForWeek($query, $weekStart)
    {
        $weekEnd = (clone $weekStart)->addDays(6)->endOfDay();
        return $query->forPeriod($weekStart, $weekEnd);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('started_at', '>=', now()->subDays($days));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_SCHEDULED,
            self::STATUS_IN_PROGRESS,
        ]);
    }

    public function getDurationHoursAttribute(): ?float
    {
        if (!$this->started_at || !$this->ended_at) {
            return null;
        }

        return round($this->started_at->diffInMinutes($this->ended_at) / 60, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Calcula horas trabalhadas se não informado.
     */
    public function calculateHoursWorked(): float
    {
        if ($this->hours_worked) {
            return (float) $this->hours_worked;
        }

        return $this->duration_hours ?? 0;
    }

    /**
     * Marca como concluído.
     */
    public function markAsCompleted(?float $hoursWorked = null): self
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'ended_at' => $this->ended_at ?? now(),
            'hours_worked' => $hoursWorked ?? $this->calculateHoursWorked(),
        ]);

        return $this;
    }

    /**
     * Marca como cancelado.
     */
    public function markAsCancelled(?string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason ? ($this->notes . "\nCancelamento: " . $reason) : $this->notes,
        ]);

        return $this;
    }
}
