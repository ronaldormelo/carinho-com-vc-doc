<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

/**
 * Agendamento de atendimento.
 *
 * @property int $id
 * @property int $assignment_id
 * @property int $caregiver_id
 * @property int $client_id
 * @property string $shift_date
 * @property string $start_time
 * @property string $end_time
 * @property int $status_id
 */
class Schedule extends Model
{
    protected $table = 'schedules';
    public $timestamps = false;

    protected $fillable = [
        'assignment_id',
        'caregiver_id',
        'client_id',
        'shift_date',
        'start_time',
        'end_time',
        'status_id',
    ];

    protected $casts = [
        'shift_date' => 'date',
    ];

    /**
     * Alocacao.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    /**
     * Status do agendamento.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainScheduleStatus::class, 'status_id');
    }

    /**
     * Registros de check-in/out.
     */
    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class, 'schedule_id');
    }

    /**
     * Check-in.
     */
    public function checkin(): HasOne
    {
        return $this->hasOne(Checkin::class, 'schedule_id')
            ->where('check_type_id', DomainCheckType::IN);
    }

    /**
     * Check-out.
     */
    public function checkout(): HasOne
    {
        return $this->hasOne(Checkin::class, 'schedule_id')
            ->where('check_type_id', DomainCheckType::OUT);
    }

    /**
     * Logs de servico.
     */
    public function serviceLogs(): HasMany
    {
        return $this->hasMany(ServiceLog::class, 'schedule_id');
    }

    /**
     * Notificacoes relacionadas.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'schedule_id');
    }

    /**
     * Retorna datetime de inicio.
     */
    public function getStartDateTimeAttribute(): Carbon
    {
        return Carbon::parse($this->shift_date->format('Y-m-d') . ' ' . $this->start_time);
    }

    /**
     * Retorna datetime de fim.
     */
    public function getEndDateTimeAttribute(): Carbon
    {
        return Carbon::parse($this->shift_date->format('Y-m-d') . ' ' . $this->end_time);
    }

    /**
     * Duracao em horas.
     */
    public function getDurationHoursAttribute(): float
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $end->diffInMinutes($start) / 60;
    }

    /**
     * Verifica se esta planejado.
     */
    public function isPlanned(): bool
    {
        return $this->status_id === DomainScheduleStatus::PLANNED;
    }

    /**
     * Verifica se esta em andamento.
     */
    public function isInProgress(): bool
    {
        return $this->status_id === DomainScheduleStatus::IN_PROGRESS;
    }

    /**
     * Verifica se foi concluido.
     */
    public function isDone(): bool
    {
        return $this->status_id === DomainScheduleStatus::DONE;
    }

    /**
     * Verifica se foi perdido/faltou.
     */
    public function isMissed(): bool
    {
        return $this->status_id === DomainScheduleStatus::MISSED;
    }

    /**
     * Verifica se ja fez check-in.
     */
    public function hasCheckedIn(): bool
    {
        return $this->checkins()->where('check_type_id', DomainCheckType::IN)->exists();
    }

    /**
     * Verifica se ja fez check-out.
     */
    public function hasCheckedOut(): bool
    {
        return $this->checkins()->where('check_type_id', DomainCheckType::OUT)->exists();
    }

    /**
     * Scope para agendamentos de hoje.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('shift_date', Carbon::today());
    }

    /**
     * Scope para agendamentos futuros.
     */
    public function scopeFuture($query)
    {
        return $query->whereDate('shift_date', '>=', Carbon::today());
    }

    /**
     * Scope por data.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('shift_date', $date);
    }

    /**
     * Scope por cuidador.
     */
    public function scopeForCaregiver($query, int $caregiverId)
    {
        return $query->where('caregiver_id', $caregiverId);
    }

    /**
     * Scope por cliente.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope para agendamentos planejados.
     */
    public function scopePlanned($query)
    {
        return $query->where('status_id', DomainScheduleStatus::PLANNED);
    }

    /**
     * Scope para agendamentos em andamento.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status_id', DomainScheduleStatus::IN_PROGRESS);
    }
}
