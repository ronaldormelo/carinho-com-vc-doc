<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de afastamentos do cuidador (atestados, férias, licenças).
 * Fundamental para gestão correta de disponibilidade.
 */
class CaregiverLeave extends Model
{
    protected $table = 'caregiver_leaves';

    protected $fillable = [
        'caregiver_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'reason',
        'document_url',
        'approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved' => 'boolean',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class, 'caregiver_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(DomainLeaveType::class, 'leave_type_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('approved', false);
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    public function scopeOfType($query, string $typeCode)
    {
        return $query->whereHas('leaveType', fn ($q) => $q->where('code', $typeCode));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getDurationDaysAttribute(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->approved &&
            $this->start_date <= now() &&
            $this->end_date >= now();
    }

    public function getIsPendingAttribute(): bool
    {
        return !$this->approved;
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->approved && $this->start_date > now();
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->leaveType?->label ?? 'Não definido';
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Aprova o afastamento.
     */
    public function approve(string $approvedBy): self
    {
        $this->update([
            'approved' => true,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'updated_at' => now(),
        ]);

        return $this;
    }

    /**
     * Rejeita o afastamento.
     */
    public function reject(string $rejectedBy, ?string $reason = null): self
    {
        $this->update([
            'approved' => false,
            'reason' => $reason ? ($this->reason . "\nRejeitado: " . $reason) : $this->reason,
            'updated_at' => now(),
        ]);

        return $this;
    }

    /**
     * Verifica se conflita com uma data específica.
     */
    public function conflictsWith(\DateTimeInterface $date): bool
    {
        return $this->approved &&
            $this->start_date <= $date &&
            $this->end_date >= $date;
    }

    /**
     * Verifica se conflita com um período.
     */
    public function conflictsWithPeriod(\DateTimeInterface $startDate, \DateTimeInterface $endDate): bool
    {
        if (!$this->approved) {
            return false;
        }

        return !($this->end_date < $startDate || $this->start_date > $endDate);
    }
}
