<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverTraining extends Model
{
    protected $table = 'caregiver_training';

    public $timestamps = false;

    protected $fillable = [
        'caregiver_id',
        'course_name',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
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
        return $query->whereNotNull('completed_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeRecentlyCompleted($query, int $days = 90)
    {
        return $query->where('completed_at', '>=', now()->subDays($days));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getIsCompletedAttribute(): bool
    {
        return $this->completed_at !== null;
    }

    public function getStatusAttribute(): string
    {
        return $this->is_completed ? 'Concluido' : 'Em andamento';
    }
}
