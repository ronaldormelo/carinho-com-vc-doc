<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverRating extends Model
{
    protected $table = 'caregiver_ratings';

    public $timestamps = false;

    protected $fillable = [
        'caregiver_id',
        'service_id',
        'score',
        'comment',
        'created_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'created_at' => 'datetime',
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

    public function scopeHighRated($query, int $minScore = 4)
    {
        return $query->where('score', '>=', $minScore);
    }

    public function scopeLowRated($query, int $maxScore = 3)
    {
        return $query->where('score', '<=', $maxScore);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeForService($query, int $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getIsPositiveAttribute(): bool
    {
        return $this->score >= 4;
    }

    public function getIsNegativeAttribute(): bool
    {
        return $this->score <= 2;
    }

    public function getIsNeutralAttribute(): bool
    {
        return $this->score === 3;
    }

    public function getScoreLabelAttribute(): string
    {
        return match ($this->score) {
            5 => 'Excelente',
            4 => 'Bom',
            3 => 'Regular',
            2 => 'Ruim',
            1 => 'Pessimo',
            default => 'N/A',
        };
    }
}
