<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasEncryptedFields;
use App\Traits\HasAuditLog;
use App\Models\Domain\DomainClientClassification;
use App\Models\Domain\DomainReviewFrequency;

/**
 * Cliente do sistema
 * 
 * Representa um cliente ativo com cadastro completo, incluindo:
 * - Classificação ABC (prática tradicional de segmentação)
 * - Responsável financeiro separado
 * - Contato de emergência (crítico para HomeCare)
 * - Controle de revisões periódicas
 * - Programa de indicações
 */
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
        // Classificação ABC
        'classification_id',
        // Responsável financeiro
        'financial_contact_name',
        'financial_contact_phone',
        'financial_contact_email',
        'financial_contact_cpf_cnpj',
        // Contato de emergência
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        // Revisões periódicas
        'review_frequency_id',
        'next_review_date',
        'last_review_date',
        // Indicações
        'referred_by_client_id',
        'referral_source',
        // Observações
        'internal_notes',
    ];

    protected $casts = [
        'preferences_json' => 'array',
        'next_review_date' => 'date',
        'last_review_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Campos criptografados (LGPD)
    protected array $encrypted = [
        'phone', 
        'address',
        'financial_contact_phone',
        'financial_contact_email',
        'emergency_contact_phone',
    ];

    // Campos auditados
    protected array $audited = [
        'primary_contact', 'phone', 'address', 'city', 'preferences_json',
        'classification_id', 'financial_contact_name', 'emergency_contact_name',
        'review_frequency_id', 'next_review_date',
    ];
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

    /**
     * Accessors para campos financeiros criptografados
     */
    public function getFinancialContactPhoneAttribute(): ?string
    {
        return $this->getDecryptedAttribute('financial_contact_phone');
    }

    public function getFinancialContactEmailAttribute(): ?string
    {
        return $this->getDecryptedAttribute('financial_contact_email');
    }

    /**
     * Accessor para contato de emergência criptografado
     */
    public function getEmergencyContactPhoneAttribute(): ?string
    {
        return $this->getDecryptedAttribute('emergency_contact_phone');
    }

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

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

    /**
     * Classificação ABC do cliente
     */
    public function classification()
    {
        return $this->belongsTo(DomainClientClassification::class, 'classification_id');
    }

    /**
     * Frequência de revisão
     */
    public function reviewFrequency()
    {
        return $this->belongsTo(DomainReviewFrequency::class, 'review_frequency_id');
    }

    /**
     * Histórico de eventos (timeline)
     */
    public function events()
    {
        return $this->hasMany(ClientEvent::class);
    }

    /**
     * Revisões periódicas
     */
    public function reviews()
    {
        return $this->hasMany(ClientReview::class);
    }

    /**
     * Cliente que indicou este
     */
    public function referredBy()
    {
        return $this->belongsTo(Client::class, 'referred_by_client_id');
    }

    /**
     * Clientes indicados por este
     */
    public function referrals()
    {
        return $this->hasMany(ClientReferral::class, 'referrer_client_id');
    }

    /**
     * Indicações recebidas (quando foi indicado)
     */
    public function referredFrom()
    {
        return $this->hasMany(ClientReferral::class, 'referred_client_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

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

    /**
     * Filtrar por classificação ABC
     */
    public function scopeClassification($query, int $classificationId)
    {
        return $query->where('classification_id', $classificationId);
    }

    /**
     * Clientes de alta prioridade (Classificação A)
     */
    public function scopeHighPriority($query)
    {
        return $query->where('classification_id', DomainClientClassification::A);
    }

    /**
     * Clientes com revisão pendente
     */
    public function scopeNeedsReview($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('next_review_date')
              ->orWhere('next_review_date', '<=', now());
        });
    }

    /**
     * Clientes com revisão próxima (nos próximos X dias)
     */
    public function scopeReviewDueSoon($query, int $days = 7)
    {
        return $query->whereBetween('next_review_date', [now(), now()->addDays($days)]);
    }

    /**
     * Clientes que foram indicados
     */
    public function scopeReferred($query)
    {
        return $query->whereNotNull('referred_by_client_id');
    }

    /**
     * Clientes sem contato de emergência (pendência importante)
     */
    public function scopeWithoutEmergencyContact($query)
    {
        return $query->whereNull('emergency_contact_phone');
    }

    // ==========================================
    // MÉTODOS DE NEGÓCIO
    // ==========================================

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

    // ==========================================
    // CLASSIFICAÇÃO ABC
    // ==========================================

    /**
     * Verificar se é cliente de alta prioridade
     */
    public function isHighPriority(): bool
    {
        return $this->classification_id === DomainClientClassification::A;
    }

    /**
     * Obter label da classificação
     */
    public function getClassificationLabelAttribute(): string
    {
        return $this->classification?->label ?? 'Não classificado';
    }

    /**
     * Definir classificação com frequência de revisão recomendada
     */
    public function setClassificationWithReview(int $classificationId): void
    {
        $this->classification_id = $classificationId;
        $this->review_frequency_id = DomainReviewFrequency::getRecommendedForClassification($classificationId);
        $this->scheduleNextReview();
    }

    // ==========================================
    // REVISÕES PERIÓDICAS
    // ==========================================

    /**
     * Verificar se precisa de revisão
     */
    public function needsReview(): bool
    {
        if ($this->next_review_date === null) {
            return true;
        }

        return $this->next_review_date->lte(now());
    }

    /**
     * Verificar se revisão está próxima
     */
    public function reviewDueSoon(int $days = 7): bool
    {
        if ($this->next_review_date === null) {
            return false;
        }

        return $this->next_review_date->between(now(), now()->addDays($days));
    }

    /**
     * Agendar próxima revisão baseada na frequência
     */
    public function scheduleNextReview(\DateTime $fromDate = null): void
    {
        if ($this->reviewFrequency) {
            $this->next_review_date = $this->reviewFrequency->calculateNextReviewDate($fromDate);
        }
    }

    /**
     * Registrar revisão realizada
     */
    public function recordReview(): void
    {
        $this->last_review_date = now();
        $this->scheduleNextReview();
        $this->save();
    }

    /**
     * Obter última revisão
     */
    public function getLastReview(): ?ClientReview
    {
        return $this->reviews()->latest('review_date')->first();
    }

    // ==========================================
    // CONTATOS
    // ==========================================

    /**
     * Verificar se tem contato de emergência
     */
    public function hasEmergencyContact(): bool
    {
        return !empty($this->emergency_contact_phone);
    }

    /**
     * Verificar se tem responsável financeiro
     */
    public function hasFinancialContact(): bool
    {
        return !empty($this->financial_contact_phone) || !empty($this->financial_contact_email);
    }

    /**
     * Obter dados do responsável financeiro formatados
     */
    public function getFinancialContactAttribute(): array
    {
        return [
            'name' => $this->financial_contact_name,
            'phone' => $this->getFinancialContactPhoneAttribute(),
            'email' => $this->getFinancialContactEmailAttribute(),
            'cpf_cnpj' => $this->financial_contact_cpf_cnpj,
        ];
    }

    /**
     * Obter dados do contato de emergência formatados
     */
    public function getEmergencyContactAttribute(): array
    {
        return [
            'name' => $this->emergency_contact_name,
            'phone' => $this->getEmergencyContactPhoneAttribute(),
            'relationship' => $this->emergency_contact_relationship,
        ];
    }

    // ==========================================
    // INDICAÇÕES
    // ==========================================

    /**
     * Verificar se foi indicado por outro cliente
     */
    public function wasReferred(): bool
    {
        return $this->referred_by_client_id !== null;
    }

    /**
     * Contar indicações convertidas
     */
    public function getConvertedReferralsCount(): int
    {
        return $this->referrals()->converted()->count();
    }

    /**
     * Obter estatísticas de indicação
     */
    public function getReferralStatsAttribute(): array
    {
        return ClientReferral::getReferrerStats($this->id);
    }

    // ==========================================
    // TIMELINE / EVENTOS
    // ==========================================

    /**
     * Obter timeline de eventos
     */
    public function getTimeline(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return $this->events()
            ->with('eventType')
            ->chronological()
            ->limit($limit)
            ->get();
    }

    /**
     * Obter timeline por categoria
     */
    public function getTimelineByCategory(string $category, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return $this->events()
            ->with('eventType')
            ->ofCategory($category)
            ->chronological()
            ->limit($limit)
            ->get();
    }

    // ==========================================
    // VERIFICAÇÕES DE CADASTRO COMPLETO
    // ==========================================

    /**
     * Verificar completude do cadastro
     */
    public function getRegistrationCompletenessAttribute(): array
    {
        $checks = [
            'basic_info' => !empty($this->primary_contact) && !empty($this->phone) && !empty($this->city),
            'classification' => $this->classification_id !== null,
            'emergency_contact' => $this->hasEmergencyContact(),
            'financial_contact' => $this->hasFinancialContact(),
            'care_needs' => $this->careNeeds()->exists(),
            'review_scheduled' => $this->review_frequency_id !== null,
            'consent' => $this->consents()->exists(),
        ];

        $completed = array_filter($checks);
        $percentage = count($checks) > 0 ? round((count($completed) / count($checks)) * 100) : 0;

        return [
            'checks' => $checks,
            'completed' => count($completed),
            'total' => count($checks),
            'percentage' => $percentage,
            'is_complete' => $percentage >= 80, // 80% é considerado completo
        ];
    }

    /**
     * Obter pendências de cadastro
     */
    public function getRegistrationPendingItems(): array
    {
        $pending = [];
        $completeness = $this->registration_completeness;

        foreach ($completeness['checks'] as $check => $status) {
            if (!$status) {
                $pending[] = match ($check) {
                    'basic_info' => 'Informações básicas incompletas',
                    'classification' => 'Classificação ABC não definida',
                    'emergency_contact' => 'Contato de emergência não cadastrado',
                    'financial_contact' => 'Responsável financeiro não cadastrado',
                    'care_needs' => 'Necessidades de cuidado não definidas',
                    'review_scheduled' => 'Frequência de revisão não definida',
                    'consent' => 'Consentimento LGPD pendente',
                    default => $check,
                };
            }
        }

        return $pending;
    }
}
