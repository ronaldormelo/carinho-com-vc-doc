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
        'issued_at',
        'expires_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'issued_at' => 'date',
        'expires_at' => 'date',
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

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    public function scopeExpiring($query, int $days = 30)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($days));
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now());
        });
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

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function getIsExpiringAttribute(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        $alertDays = config('cuidadores.operacional.document_expiry_alert_days', 30);
        return $this->expires_at > now() && $this->expires_at <= now()->addDays($alertDays);
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }
        return now()->diffInDays($this->expires_at, false);
    }

    public function getExpiryStatusAttribute(): string
    {
        if (!$this->expires_at) {
            return 'no_expiry';
        }

        if ($this->is_expired) {
            return 'expired';
        }

        if ($this->is_expiring) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    public function getIsValidAttribute(): bool
    {
        return $this->is_verified && !$this->is_expired;
    }
}
