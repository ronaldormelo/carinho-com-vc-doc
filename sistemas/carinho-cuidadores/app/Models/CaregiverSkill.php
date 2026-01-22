<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverSkill extends Model
{
    protected $table = 'caregiver_skills';

    public $timestamps = false;

    protected $fillable = [
        'caregiver_id',
        'care_type_id',
        'level_id',
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

    public function careType(): BelongsTo
    {
        return $this->belongsTo(DomainCareType::class, 'care_type_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(DomainSkillLevel::class, 'level_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOfType($query, string $typeCode)
    {
        return $query->whereHas('careType', fn ($q) => $q->where('code', $typeCode));
    }

    public function scopeOfLevel($query, string $levelCode)
    {
        return $query->whereHas('level', fn ($q) => $q->where('code', $levelCode));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getDisplayNameAttribute(): string
    {
        $type = $this->careType?->label ?? 'N/A';
        $level = $this->level?->label ?? 'N/A';
        return "{$type} ({$level})";
    }
}
