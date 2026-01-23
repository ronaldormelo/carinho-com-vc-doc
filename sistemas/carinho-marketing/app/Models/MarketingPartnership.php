<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Domain\DomainPartnershipType;
use App\Models\Domain\DomainPartnershipStatus;
use Illuminate\Support\Str;

/**
 * Model para parcerias locais.
 * 
 * Gerencia parcerias com clínicas, hospitais, cuidadores,
 * condomínios e outros estabelecimentos para indicações.
 */
class MarketingPartnership extends Model
{
    protected $table = 'marketing_partnerships';

    protected $fillable = [
        'name',
        'type_id',
        'status_id',
        'contact_name',
        'contact_phone',
        'contact_email',
        'address',
        'city',
        'state',
        'notes',
        'commission_percent',
        'referral_code',
    ];

    protected $casts = [
        'commission_percent' => 'decimal:2',
    ];

    /**
     * Boot do model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->referral_code)) {
                $model->referral_code = self::generateReferralCode();
            }
        });
    }

    /**
     * Relacionamento com tipo de parceria.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DomainPartnershipType::class, 'type_id');
    }

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainPartnershipStatus::class, 'status_id');
    }

    /**
     * Relacionamento com indicações.
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(PartnershipReferral::class, 'partnership_id');
    }

    /**
     * Verifica se está ativa.
     */
    public function isActive(): bool
    {
        return $this->status_id === DomainPartnershipStatus::ACTIVE;
    }

    /**
     * Scope para parcerias ativas.
     */
    public function scopeActive($query)
    {
        return $query->where('status_id', DomainPartnershipStatus::ACTIVE);
    }

    /**
     * Scope por tipo.
     */
    public function scopeOfType($query, int $typeId)
    {
        return $query->where('type_id', $typeId);
    }

    /**
     * Scope por cidade.
     */
    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Gera código de indicação único.
     */
    public static function generateReferralCode(): string
    {
        do {
            $code = 'P' . strtoupper(Str::random(7));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Encontra parceria pelo código de indicação.
     */
    public static function findByReferralCode(string $code): ?self
    {
        return self::where('referral_code', $code)->first();
    }

    /**
     * Conta total de indicações.
     */
    public function getTotalReferrals(): int
    {
        return $this->referrals()->count();
    }

    /**
     * Conta indicações convertidas.
     */
    public function getConvertedReferrals(): int
    {
        return $this->referrals()->where('converted', true)->count();
    }

    /**
     * Taxa de conversão.
     */
    public function getConversionRate(): ?float
    {
        $total = $this->getTotalReferrals();
        if ($total === 0) {
            return null;
        }

        return round(($this->getConvertedReferrals() / $total) * 100, 2);
    }

    /**
     * Valor total gerado.
     */
    public function getTotalContractValue(): float
    {
        return $this->referrals()
            ->where('converted', true)
            ->sum('contract_value') ?? 0;
    }

    /**
     * Comissão total a pagar.
     */
    public function getPendingCommission(): float
    {
        return $this->referrals()
            ->where('converted', true)
            ->where('commission_paid', false)
            ->sum('commission_value') ?? 0;
    }
}
