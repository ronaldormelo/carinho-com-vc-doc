<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de atividades realizadas durante o servico.
 *
 * @property int $id
 * @property int $schedule_id
 * @property array $activities_json
 * @property ?string $notes
 * @property string $created_at
 */
class ServiceLog extends Model
{
    protected $table = 'service_logs';
    public $timestamps = false;

    protected $fillable = [
        'schedule_id',
        'activities_json',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'activities_json' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Boot model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    /**
     * Agendamento associado.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    /**
     * Retorna lista de atividades.
     */
    public function getActivitiesAttribute(): array
    {
        return $this->activities_json ?? [];
    }

    /**
     * Adiciona uma atividade ao log.
     */
    public function addActivity(string $activity): self
    {
        $activities = $this->activities_json ?? [];
        $activities[] = [
            'activity' => $activity,
            'logged_at' => now()->toIso8601String(),
        ];
        $this->activities_json = $activities;
        $this->save();

        return $this;
    }

    /**
     * Conta numero de atividades.
     */
    public function getActivityCountAttribute(): int
    {
        return count($this->activities_json ?? []);
    }

    /**
     * Scope por agendamento.
     */
    public function scopeForSchedule($query, int $scheduleId)
    {
        return $query->where('schedule_id', $scheduleId);
    }
}
