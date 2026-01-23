<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class BankAccount extends Model
{
    protected $table = 'bank_accounts';

    protected $fillable = [
        'owner_type_id',
        'owner_id',
        'bank_name',
        'bank_code',
        'account_hash',
        'account_type',
        'holder_name',
        'holder_document',
        'pix_key',
        'pix_key_type',
        'stripe_external_account_id',
        'is_default',
        'verified_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Campos sensíveis que devem ser ocultos.
     */
    protected $hidden = [
        'account_hash',
        'holder_document',
        'pix_key',
    ];

    /**
     * Accessor/Mutator para dados bancários criptografados.
     */
    protected function accountHash(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Accessor/Mutator para documento do titular criptografado.
     */
    protected function holderDocument(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Accessor/Mutator para chave PIX criptografada.
     */
    protected function pixKey(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Relacionamento com tipo de proprietário.
     */
    public function ownerType(): BelongsTo
    {
        return $this->belongsTo(DomainOwnerType::class, 'owner_type_id');
    }

    /**
     * Verifica se é conta de cuidador.
     */
    public function isCaregiver(): bool
    {
        return $this->owner_type_id === DomainOwnerType::CAREGIVER;
    }

    /**
     * Verifica se é conta de cliente.
     */
    public function isClient(): bool
    {
        return $this->owner_type_id === DomainOwnerType::CLIENT;
    }

    /**
     * Verifica se é conta da empresa.
     */
    public function isCompany(): bool
    {
        return $this->owner_type_id === DomainOwnerType::COMPANY;
    }

    /**
     * Verifica se a conta foi verificada.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Marca como verificada.
     */
    public function markAsVerified(): self
    {
        $this->verified_at = now();
        $this->save();
        return $this;
    }

    /**
     * Define como conta padrão.
     */
    public function setAsDefault(): self
    {
        // Remove padrão de outras contas do mesmo proprietário
        static::where('owner_type_id', $this->owner_type_id)
            ->where('owner_id', $this->owner_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->is_default = true;
        $this->save();

        return $this;
    }

    /**
     * Verifica se tem chave PIX.
     */
    public function hasPixKey(): bool
    {
        return !empty($this->pix_key);
    }

    /**
     * Verifica se tem conta Stripe vinculada.
     */
    public function hasStripeAccount(): bool
    {
        return !empty($this->stripe_external_account_id);
    }

    /**
     * Retorna dados mascarados da conta para exibição.
     */
    public function getMaskedAccountAttribute(): string
    {
        $accountData = $this->account_hash;
        if (!$accountData) {
            return '****';
        }

        $data = json_decode($accountData, true);
        $agency = $data['agency'] ?? '****';
        $account = $data['account'] ?? '****';

        // Mascara a conta, mostrando apenas últimos 4 dígitos
        $maskedAccount = str_repeat('*', max(0, strlen($account) - 4)) . substr($account, -4);

        return "Ag: {$agency} / Conta: {$maskedAccount}";
    }

    /**
     * Scope para contas de cuidadores.
     */
    public function scopeCaregiver($query)
    {
        return $query->where('owner_type_id', DomainOwnerType::CAREGIVER);
    }

    /**
     * Scope para contas padrão.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope para contas verificadas.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Scope para contas de um proprietário específico.
     */
    public function scopeForOwner($query, int $ownerTypeId, int $ownerId)
    {
        return $query->where('owner_type_id', $ownerTypeId)
            ->where('owner_id', $ownerId);
    }
}
