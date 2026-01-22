<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverContract extends Model
{
    protected $table = 'caregiver_contracts';

    public $timestamps = false;

    protected $fillable = [
        'caregiver_id',
        'contract_id',
        'status_id',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
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
        return $this->belongsTo(DomainContractStatus::class, 'status_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDraft($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('code', 'draft'));
    }

    public function scopeSigned($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('code', 'signed'));
    }

    public function scopeActive($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('code', 'active'));
    }

    public function scopeClosed($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('code', 'closed'));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getIsSignedAttribute(): bool
    {
        return $this->status?->code === 'signed' || $this->status?->code === 'active';
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status?->code === 'active';
    }
}
