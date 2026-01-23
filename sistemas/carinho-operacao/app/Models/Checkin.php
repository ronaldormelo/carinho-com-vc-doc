<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de check-in ou check-out.
 *
 * @property int $id
 * @property int $schedule_id
 * @property int $check_type_id
 * @property string $timestamp
 * @property ?string $location
 */
class Checkin extends Model
{
    protected $table = 'checkins';
    public $timestamps = false;

    protected $fillable = [
        'schedule_id',
        'check_type_id',
        'timestamp',
        'location',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    /**
     * Agendamento associado.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    /**
     * Tipo de check (in ou out).
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DomainCheckType::class, 'check_type_id');
    }

    /**
     * Verifica se e check-in.
     */
    public function isCheckIn(): bool
    {
        return $this->check_type_id === DomainCheckType::IN;
    }

    /**
     * Verifica se e check-out.
     */
    public function isCheckOut(): bool
    {
        return $this->check_type_id === DomainCheckType::OUT;
    }

    /**
     * Verifica se esta atrasado em relacao ao horario esperado.
     */
    public function isLate(): bool
    {
        $schedule = $this->schedule;
        if (!$schedule) {
            return false;
        }

        $expectedTime = $this->isCheckIn()
            ? $schedule->start_date_time
            : $schedule->end_date_time;

        $tolerance = config('operacao.checkin.late_tolerance_minutes', 15);

        return $this->timestamp->gt($expectedTime->addMinutes($tolerance));
    }

    /**
     * Calcula diferenca em minutos do horario esperado.
     */
    public function getDelayMinutesAttribute(): int
    {
        $schedule = $this->schedule;
        if (!$schedule) {
            return 0;
        }

        $expectedTime = $this->isCheckIn()
            ? $schedule->start_date_time
            : $schedule->end_date_time;

        return (int) $this->timestamp->diffInMinutes($expectedTime, false);
    }

    /**
     * Scope para check-ins.
     */
    public function scopeCheckIns($query)
    {
        return $query->where('check_type_id', DomainCheckType::IN);
    }

    /**
     * Scope para check-outs.
     */
    public function scopeCheckOuts($query)
    {
        return $query->where('check_type_id', DomainCheckType::OUT);
    }
}
