<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasEncryptedFields;
use App\Traits\HasAuditLog;

class Client extends Model
{
    use HasFactory, HasEncryptedFields, HasAuditLog;

    protected $table = 'clients';

    protected $fillable = [
        'lead_id',
        'primary_contact',
        'phone',
        'address',
        'city',
        'preferences_json',
    ];

    protected $casts = [
        'preferences_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Campos criptografados (LGPD)
    protected array $encrypted = ['phone', 'address'];

    // Campos auditados
    protected array $audited = ['primary_contact', 'phone', 'address', 'city', 'preferences_json'];
    protected string $logName = 'clients';

    /**
     * Accessor para telefone descriptografado
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->getDecryptedAttribute('phone');
    }

    /**
     * Accessor para endereço descriptografado
     */
    public function getAddressAttribute(): ?string
    {
        return $this->getDecryptedAttribute('address');
    }

    // Relacionamentos
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function careNeeds()
    {
        return $this->hasMany(CareNeed::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function consents()
    {
        return $this->hasMany(Consent::class);
    }

    // Scopes
    public function scopeByCity($query, string $city)
    {
        return $query->where('city', 'LIKE', "%{$city}%");
    }

    public function scopeWithActiveContracts($query)
    {
        return $query->whereHas('contracts', function ($q) {
            $q->whereIn('status_id', \App\Models\Domain\DomainContractStatus::activeStatuses());
        });
    }

    // Métodos de negócio
    public function hasActiveContract(): bool
    {
        return $this->contracts()
            ->whereIn('status_id', \App\Models\Domain\DomainContractStatus::activeStatuses())
            ->exists();
    }

    public function getActiveContract(): ?Contract
    {
        return $this->contracts()
            ->whereIn('status_id', \App\Models\Domain\DomainContractStatus::activeStatuses())
            ->latest()
            ->first();
    }

    public function hasValidConsent(string $consentType): bool
    {
        return $this->consents()
            ->where('consent_type', $consentType)
            ->exists();
    }

    public function getPreference(string $key, $default = null)
    {
        return data_get($this->preferences_json, $key, $default);
    }

    public function setPreference(string $key, $value): void
    {
        $preferences = $this->preferences_json ?? [];
        data_set($preferences, $key, $value);
        $this->preferences_json = $preferences;
    }

    /**
     * Obter nome completo (do lead original ou contato principal)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->lead?->name ?? $this->primary_contact;
    }
}
