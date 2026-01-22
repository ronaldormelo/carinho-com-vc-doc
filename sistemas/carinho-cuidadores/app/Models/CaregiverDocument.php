<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverDocument extends Model
{
    protected $table = 'caregiver_documents';

    public $timestamps = false;

    protected $fillable = [
        'caregiver_id',
        'doc_type_id',
        'file_url',
        'status_id',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
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

    public function docType(): BelongsTo
    {
        return $this->belongsTo(DomainDocumentType::class, 'doc_type_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainDocumentStatus::class, 'status_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('code', 'pending'));
    }

    public function scopeVerified($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('code', 'verified'));
    }

    public function scopeRejected($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('code', 'rejected'));
    }

    public function scopeOfType($query, string $typeCode)
    {
        return $query->whereHas('docType', fn ($q) => $q->where('code', $typeCode));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getIsVerifiedAttribute(): bool
    {
        return $this->status?->code === 'verified';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status?->code === 'pending';
    }

    public function getIsRejectedAttribute(): bool
    {
        return $this->status?->code === 'rejected';
    }
}
