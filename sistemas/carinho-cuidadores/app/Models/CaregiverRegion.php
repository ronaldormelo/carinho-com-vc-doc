<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverRegion extends Model
{
    protected $table = 'caregiver_regions';

    public $timestamps = false;

    protected $fillable = [
        'caregiver_id',
        'city',
        'neighborhood',
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

    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeInNeighborhood($query, string $neighborhood)
    {
        return $query->where('neighborhood', $neighborhood);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getDisplayAttribute(): string
    {
        if ($this->neighborhood) {
            return "{$this->neighborhood}, {$this->city}";
        }
        return $this->city;
    }
}
