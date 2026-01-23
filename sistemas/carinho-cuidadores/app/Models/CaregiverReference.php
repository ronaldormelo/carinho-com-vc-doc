<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Referências profissionais do cuidador.
 * Permite verificar histórico e reputação.
 */
class CaregiverReference extends Model
{
    protected $table = 'caregiver_references';

    public $timestamps = false;

    protected $fillable = [
        'caregiver_id',
        'name',
        'phone',
        'relationship',
        'company',
        'position',
        'verified',
        'verified_at',
        'verification_notes',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Tipos de relacionamento com a referência
     */
    public const RELATIONSHIPS = [
        'employer' => 'Empregador Anterior',
        'supervisor' => 'Supervisor',
        'coworker' => 'Colega de Trabalho',
        'client' => 'Cliente Anterior',
        'professional' => 'Referência Profissional',
        'personal' => 'Referência Pessoal',
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

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopePendingVerification($query)
    {
        return $query->where('verified', false);
    }

    public function scopeProfessional($query)
    {
        return $query->whereIn('relationship', ['employer', 'supervisor', 'client', 'professional']);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getRelationshipLabelAttribute(): string
    {
        return self::RELATIONSHIPS[$this->relationship] ?? $this->relationship;
    }

    public function getIsVerifiedAttribute(): bool
    {
        return (bool) $this->verified;
    }

    public function getIsProfessionalAttribute(): bool
    {
        return in_array($this->relationship, ['employer', 'supervisor', 'client', 'professional']);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Marca referência como verificada.
     */
    public function markAsVerified(?string $notes = null): self
    {
        $this->update([
            'verified' => true,
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Formata o telefone para exibição.
     */
    public function getFormattedPhone(): string
    {
        $phone = preg_replace('/\D/', '', $this->phone ?? '');
        
        if (strlen($phone) === 11) {
            return '(' . substr($phone, 0, 2) . ') ' . 
                   substr($phone, 2, 5) . '-' . 
                   substr($phone, 7);
        }
        
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 2) . ') ' . 
                   substr($phone, 2, 4) . '-' . 
                   substr($phone, 6);
        }
        
        return $this->phone ?? '';
    }
}
