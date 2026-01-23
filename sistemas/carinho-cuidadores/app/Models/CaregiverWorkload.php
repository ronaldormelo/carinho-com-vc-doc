<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Controle de carga de trabalho semanal do cuidador.
 * Permite monitorar horas trabalhadas e evitar sobrecarga.
 */
class CaregiverWorkload extends Model
{
    protected $table = 'caregiver_workload';

    protected $fillable = [
        'caregiver_id',
        'week_start',
        'week_end',
        'hours_scheduled',
        'hours_worked',
        'hours_overtime',
        'assignments_count',
        'clients_count',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'hours_scheduled' => 'decimal:2',
        'hours_worked' => 'decimal:2',
        'hours_overtime' => 'decimal:2',
        'assignments_count' => 'integer',
        'clients_count' => 'integer',
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

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForWeek($query, $weekStart)
    {
        return $query->where('week_start', $weekStart);
    }

    public function scopeCurrentWeek($query)
    {
        return $query->where('week_start', now()->startOfWeek()->format('Y-m-d'));
    }

    public function scopeWithOvertime($query)
    {
        return $query->where('hours_overtime', '>', 0);
    }

    public function scopeOverloaded($query, ?float $maxHours = null)
    {
        $maxHours = $maxHours ?? config('cuidadores.operacional.max_weekly_hours', 44);
        return $query->where('hours_worked', '>', $maxHours);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getAvailableHoursAttribute(): float
    {
        $maxHours = config('cuidadores.operacional.max_weekly_hours', 44);
        return max(0, $maxHours - (float) $this->hours_worked);
    }

    public function getUtilizationRateAttribute(): float
    {
        $maxHours = config('cuidadores.operacional.max_weekly_hours', 44);
        if ($maxHours <= 0) {
            return 0;
        }
        return round(((float) $this->hours_worked / $maxHours) * 100, 1);
    }

    public function getIsOverloadedAttribute(): bool
    {
        $maxHours = config('cuidadores.operacional.max_weekly_hours', 44);
        return (float) $this->hours_worked > $maxHours;
    }

    public function getNeedsOvertimeAlertAttribute(): bool
    {
        $alertHours = config('cuidadores.operacional.overtime_alert_hours', 40);
        return (float) $this->hours_worked >= $alertHours;
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Obtém ou cria registro para a semana atual de um cuidador.
     */
    public static function getOrCreateForWeek(int $caregiverId, ?\DateTimeInterface $weekStart = null): self
    {
        $weekStart = $weekStart ?? now()->startOfWeek();
        $weekEnd = (clone $weekStart)->addDays(6);

        return self::firstOrCreate(
            [
                'caregiver_id' => $caregiverId,
                'week_start' => $weekStart->format('Y-m-d'),
            ],
            [
                'week_end' => $weekEnd->format('Y-m-d'),
                'hours_scheduled' => 0,
                'hours_worked' => 0,
                'hours_overtime' => 0,
                'assignments_count' => 0,
                'clients_count' => 0,
                'created_at' => now(),
            ]
        );
    }

    /**
     * Recalcula totais baseado nas alocações da semana.
     */
    public function recalculate(): self
    {
        $assignments = CaregiverAssignment::where('caregiver_id', $this->caregiver_id)
            ->forWeek($this->week_start)
            ->get();

        $hoursScheduled = $assignments
            ->whereIn('status', [CaregiverAssignment::STATUS_SCHEDULED, CaregiverAssignment::STATUS_IN_PROGRESS])
            ->sum(fn ($a) => $a->duration_hours ?? 0);

        $hoursWorked = $assignments
            ->where('status', CaregiverAssignment::STATUS_COMPLETED)
            ->sum(fn ($a) => $a->hours_worked ?? 0);

        $maxHours = config('cuidadores.operacional.max_weekly_hours', 44);
        $hoursOvertime = max(0, $hoursWorked - $maxHours);

        $clientIds = $assignments->pluck('client_id')->filter()->unique();

        $this->update([
            'hours_scheduled' => round($hoursScheduled, 2),
            'hours_worked' => round($hoursWorked, 2),
            'hours_overtime' => round($hoursOvertime, 2),
            'assignments_count' => $assignments->count(),
            'clients_count' => $clientIds->count(),
            'updated_at' => now(),
        ]);

        return $this;
    }

    /**
     * Adiciona horas trabalhadas.
     */
    public function addHoursWorked(float $hours): self
    {
        $newTotal = (float) $this->hours_worked + $hours;
        $maxHours = config('cuidadores.operacional.max_weekly_hours', 44);
        $overtime = max(0, $newTotal - $maxHours);

        $this->update([
            'hours_worked' => round($newTotal, 2),
            'hours_overtime' => round($overtime, 2),
            'updated_at' => now(),
        ]);

        return $this;
    }
}
