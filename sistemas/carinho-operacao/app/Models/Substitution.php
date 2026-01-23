<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de substituicao de cuidador.
 *
 * @property int $id
 * @property int $assignment_id
 * @property int $old_caregiver_id
 * @property int $new_caregiver_id
 * @property string $reason
 * @property string $created_at
 */
class Substitution extends Model
{
    protected $table = 'substitutions';
    public $timestamps = false;

    protected $fillable = [
        'assignment_id',
        'old_caregiver_id',
        'new_caregiver_id',
        'reason',
        'created_at',
    ];

    protected $casts = [
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
     * Alocacao associada.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    /**
     * Motivos de substituicao comuns.
     */
    public static function reasons(): array
    {
        return [
            'illness' => 'Doenca do cuidador',
            'emergency' => 'Emergencia pessoal',
            'no_show' => 'Nao comparecimento',
            'client_request' => 'Solicitacao do cliente',
            'schedule_conflict' => 'Conflito de agenda',
            'performance' => 'Questoes de desempenho',
            'other' => 'Outro motivo',
        ];
    }

    /**
     * Scope por cuidador substituido.
     */
    public function scopeFromCaregiver($query, int $caregiverId)
    {
        return $query->where('old_caregiver_id', $caregiverId);
    }

    /**
     * Scope por cuidador substituto.
     */
    public function scopeToCaregiver($query, int $caregiverId)
    {
        return $query->where('new_caregiver_id', $caregiverId);
    }

    /**
     * Scope por motivo.
     */
    public function scopeWithReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }
}
