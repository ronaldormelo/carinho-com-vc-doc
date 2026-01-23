<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caregiver extends Model
{
    protected $table = 'caregivers';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'city',
        'status_id',
        'experience_years',
        'profile_summary',
    ];

    protected $casts = [
        'experience_years' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainCaregiverStatus::class, 'status_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CaregiverDocument::class, 'caregiver_id');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(CaregiverSkill::class, 'caregiver_id');
    }

    public function availability(): HasMany
    {
        return $this->hasMany(CaregiverAvailability::class, 'caregiver_id');
    }

    public function regions(): HasMany
    {
        return $this->hasMany(CaregiverRegion::class, 'caregiver_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(CaregiverContract::class, 'caregiver_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(CaregiverRating::class, 'caregiver_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(CaregiverIncident::class, 'caregiver_id');
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(CaregiverTraining::class, 'caregiver_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(CaregiverStatusHistory::class, 'caregiver_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('code', 'active'));
    }

    public function scopePending($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('code', 'pending'));
    }

    public function scopeInactive($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('code', 'inactive'));
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByRegion($query, string $city, ?string $neighborhood = null)
    {
        return $query->whereHas('regions', function ($q) use ($city, $neighborhood) {
            $q->where('city', $city);
            if ($neighborhood) {
                $q->where('neighborhood', $neighborhood);
            }
        });
    }

    public function scopeBySkill($query, string $careTypeCode)
    {
        return $query->whereHas('skills', function ($q) use ($careTypeCode) {
            $q->whereHas('careType', fn ($q2) => $q2->where('code', $careTypeCode));
        });
    }

    public function scopeAvailableOn($query, int $dayOfWeek)
    {
        return $query->whereHas('availability', fn ($q) => $q->where('day_of_week', $dayOfWeek));
    }

    public function scopeWithMinExperience($query, int $years)
    {
        return $query->where('experience_years', '>=', $years);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->ratings()->avg('score');
        return $avg ? round($avg, 2) : null;
    }

    public function getTotalRatingsAttribute(): int
    {
        return $this->ratings()->count();
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status?->code === 'active';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status?->code === 'pending';
    }

    public function getHasAllRequiredDocumentsAttribute(): bool
    {
        $required = config('cuidadores.triagem.documentos_obrigatorios', []);
        $verified = $this->documents()
            ->whereHas('status', fn ($q) => $q->where('code', 'verified'))
            ->whereHas('docType', fn ($q) => $q->whereIn('code', $required))
            ->pluck('doc_type_id')
            ->unique()
            ->count();

        return $verified >= count($required);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function normalizedPhone(): string
    {
        return preg_replace('/\D+/', '', $this->phone ?? '');
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'city' => $this->city,
            'status' => $this->status?->code,
            'experience_years' => $this->experience_years,
            'average_rating' => $this->average_rating,
            'skills' => $this->skills->map(fn ($s) => $s->careType?->code)->toArray(),
            'regions' => $this->regions->map(fn ($r) => [
                'city' => $r->city,
                'neighborhood' => $r->neighborhood,
            ])->toArray(),
        ];
    }
}
