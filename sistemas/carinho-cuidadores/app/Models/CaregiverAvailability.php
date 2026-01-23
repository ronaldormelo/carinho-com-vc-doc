<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverAvailability extends Model
{
    protected $table = 'caregiver_availability';

    public $timestamps = false;

    protected $fillable = [
        'caregiver_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    /**
     * Dias da semana (0 = Domingo, 6 = Sabado)
     */
    public const DAYS = [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terca-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sabado',
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

    public function scopeOnDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeWeekdays($query)
    {
        return $query->whereBetween('day_of_week', [1, 5]);
    }

    public function scopeWeekends($query)
    {
        return $query->whereIn('day_of_week', [0, 6]);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? 'N/A';
    }

    public function getFormattedTimeRangeAttribute(): string
    {
        $start = substr($this->start_time, 0, 5);
        $end = substr($this->end_time, 0, 5);
        return "{$start} - {$end}";
    }

    public function getDisplayAttribute(): string
    {
        return "{$this->day_name}: {$this->formatted_time_range}";
    }
}
