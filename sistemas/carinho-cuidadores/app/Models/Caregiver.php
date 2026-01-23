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
        'cpf',
        'birth_date',
        'email',
        'city',
        'address_street',
        'address_number',
        'address_complement',
        'address_neighborhood',
        'address_zipcode',
        'address_state',
        'status_id',
        'experience_years',
        'profile_summary',
        'emergency_contact_name',
        'emergency_contact_phone',
        'recruitment_source',
        'referred_by_caregiver_id',
    ];

    protected $casts = [
        'experience_years' => 'integer',
        'birth_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Fontes de recrutamento válidas
     */
    public const RECRUITMENT_SOURCES = [
        'website' => 'Site',
        'whatsapp' => 'WhatsApp',
        'referral' => 'Indicação',
        'social_media' => 'Redes Sociais',
        'job_board' => 'Sites de Emprego',
        'partnership' => 'Parceria',
        'other' => 'Outro',
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

    public function assignments(): HasMany
    {
        return $this->hasMany(CaregiverAssignment::class, 'caregiver_id');
    }

    public function workloads(): HasMany
    {
        return $this->hasMany(CaregiverWorkload::class, 'caregiver_id');
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(CaregiverLeave::class, 'caregiver_id');
    }

    public function references(): HasMany
    {
        return $this->hasMany(CaregiverReference::class, 'caregiver_id');
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class, 'referred_by_caregiver_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Caregiver::class, 'referred_by_caregiver_id');
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

    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }
        return $this->birth_date->age;
    }

    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->address_street,
            $this->address_number,
            $this->address_complement,
            $this->address_neighborhood,
            $this->city,
            $this->address_state,
            $this->address_zipcode,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    public function getFormattedCpfAttribute(): ?string
    {
        if (!$this->cpf) {
            return null;
        }
        $cpf = preg_replace('/\D/', '', $this->cpf);
        if (strlen($cpf) !== 11) {
            return $this->cpf;
        }
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . 
               substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }

    public function getCurrentWeekHoursAttribute(): float
    {
        $weekStart = now()->startOfWeek();
        $workload = $this->workloads()
            ->where('week_start', $weekStart->format('Y-m-d'))
            ->first();

        return $workload ? (float) $workload->hours_worked : 0;
    }

    public function getIsOnLeaveAttribute(): bool
    {
        return $this->leaves()
            ->where('approved', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->exists();
    }

    public function getHasExpiringDocumentsAttribute(): bool
    {
        $alertDays = config('cuidadores.operacional.document_expiry_alert_days', 30);
        
        return $this->documents()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($alertDays))
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function getHasExpiredDocumentsAttribute(): bool
    {
        return $this->documents()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->exists();
    }

    public function getTotalHoursWorkedAttribute(): float
    {
        return (float) $this->assignments()
            ->whereNotNull('hours_worked')
            ->sum('hours_worked');
    }

    public function getTotalAssignmentsAttribute(): int
    {
        return $this->assignments()->count();
    }

    public function getRecentIncidentsCountAttribute(): int
    {
        return $this->incidents()
            ->where('occurred_at', '>=', now()->subDays(90))
            ->count();
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

    /**
     * Verifica se cuidador pode trabalhar mais horas na semana.
     */
    public function canWorkMoreHours(float $additionalHours = 0): bool
    {
        $maxHours = config('cuidadores.operacional.max_weekly_hours', 44);
        $currentHours = $this->current_week_hours;
        
        return ($currentHours + $additionalHours) <= $maxHours;
    }

    /**
     * Verifica se está disponível em uma data específica.
     */
    public function isAvailableOn(\DateTimeInterface $date): bool
    {
        // Verifica se está afastado
        $onLeave = $this->leaves()
            ->where('approved', true)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();

        if ($onLeave) {
            return false;
        }

        // Verifica se tem disponibilidade no dia da semana
        $dayOfWeek = $date->format('w');
        return $this->availability()
            ->where('day_of_week', $dayOfWeek)
            ->exists();
    }

    /**
     * Retorna documentos que estão vencendo.
     */
    public function getExpiringDocuments(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return $this->documents()
            ->with('docType')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now())
            ->orderBy('expires_at')
            ->get();
    }

    /**
     * Valida CPF (formato brasileiro).
     */
    public static function validateCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        
        if (strlen($cpf) !== 11) {
            return false;
        }
        
        // Verifica CPFs com todos os dígitos iguais
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        
        // Validação dos dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Normaliza CPF removendo formatação.
     */
    public static function normalizeCpf(string $cpf): string
    {
        return preg_replace('/\D/', '', $cpf);
    }
}
