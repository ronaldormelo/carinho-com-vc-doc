<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverStatusHistory extends Model
{
    protected $table = 'caregiver_status_history';

    public $timestamps = false;

    protected $fillable = [
        'caregiver_id',
        'status_id',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
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

    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainCaregiverStatus::class, 'status_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('changed_at', '>=', now()->subDays($days));
    }

    public function scopeToStatus($query, string $statusCode)
    {
        return $query->whereHas('status', fn ($q) => $q->where('code', $statusCode));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label ?? 'N/A';
    }
}
