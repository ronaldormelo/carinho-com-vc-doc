<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasAuditLog;
use App\Models\Domain\DomainContractStatus;

/**
 * Contrato de serviço
 * 
 * Representa um contrato entre a empresa e o cliente.
 * Inclui práticas tradicionais de gestão de contratos:
 * - Alertas de renovação configuráveis por contrato
 * - Controle de renovações automáticas
 * - Histórico de renovações
 */
class Contract extends Model
{
    use HasFactory, HasAuditLog;

    protected $table = 'contracts';
    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'proposal_id',
        'status_id',
        'signed_at',
        'start_date',
        'end_date',
        // Alertas configuráveis (prática tradicional)
        'renewal_alert_days',
        'last_renewal_alert_at',
        // Renovação
        'auto_renewal',
        'renewal_count',
        'original_contract_id',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_renewal_alert_at' => 'date',
        'auto_renewal' => 'boolean',
        'renewal_count' => 'integer',
        'renewal_alert_days' => 'integer',
    ];

    // Dias padrão para alerta de renovação por tipo de serviço
    public const ALERT_DAYS_DEFAULT = 30;
    public const ALERT_DAYS_MONTHLY = 15;
    public const ALERT_DAYS_QUARTERLY = 30;
    public const ALERT_DAYS_ANNUAL = 60;

    // Campos auditados
    protected array $audited = [
        'status_id', 'signed_at', 'start_date', 'end_date',
        'renewal_alert_days', 'auto_renewal'
    ];
    protected string $logName = 'contracts';

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function status()
    {
        return $this->belongsTo(DomainContractStatus::class, 'status_id');
    }

    /**
     * Contrato original (para contratos renovados)
     */
    public function originalContract()
    {
        return $this->belongsTo(Contract::class, 'original_contract_id');
    }

    /**
     * Renovações deste contrato
     */
    public function renewals()
    {
        return $this->hasMany(Contract::class, 'original_contract_id');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status_id', DomainContractStatus::DRAFT);
    }

    public function scopeSigned($query)
    {
        return $query->where('status_id', DomainContractStatus::SIGNED);
    }

    public function scopeActive($query)
    {
        return $query->where('status_id', DomainContractStatus::ACTIVE);
    }

    public function scopeClosed($query)
    {
        return $query->where('status_id', DomainContractStatus::CLOSED);
    }

    public function scopeVigente($query)
    {
        return $query->whereIn('status_id', DomainContractStatus::activeStatuses());
    }

    public function scopeExpiringIn($query, int $days)
    {
        return $query->where('end_date', '<=', now()->addDays($days))
                     ->where('end_date', '>=', now())
                     ->whereIn('status_id', DomainContractStatus::activeStatuses());
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now())
                     ->whereIn('status_id', DomainContractStatus::activeStatuses());
    }

    /**
     * Contratos que precisam de alerta de renovação
     * (Considera o período configurado por contrato)
     */
    public function scopeNeedingRenewalAlert($query)
    {
        return $query->whereIn('status_id', DomainContractStatus::activeStatuses())
            ->whereRaw('end_date <= DATE_ADD(NOW(), INTERVAL renewal_alert_days DAY)')
            ->where('end_date', '>=', now())
            ->where(function ($q) {
                $q->whereNull('last_renewal_alert_at')
                  ->orWhereRaw('last_renewal_alert_at < DATE_SUB(end_date, INTERVAL renewal_alert_days DAY)');
            });
    }

    /**
     * Contratos com renovação automática
     */
    public function scopeAutoRenewal($query)
    {
        return $query->where('auto_renewal', true);
    }

    /**
     * Contratos renovados (tem original)
     */
    public function scopeIsRenewal($query)
    {
        return $query->whereNotNull('original_contract_id');
    }

    /**
     * Contratos originais (não são renovação)
     */
    public function scopeIsOriginal($query)
    {
        return $query->whereNull('original_contract_id');
    }

    // ==========================================
    // MÉTODOS DE NEGÓCIO
    // ==========================================
    public function isDraft(): bool
    {
        return $this->status_id === DomainContractStatus::DRAFT;
    }

    public function isSigned(): bool
    {
        return $this->status_id === DomainContractStatus::SIGNED;
    }

    public function isActive(): bool
    {
        return $this->status_id === DomainContractStatus::ACTIVE;
    }

    public function isClosed(): bool
    {
        return $this->status_id === DomainContractStatus::CLOSED;
    }

    public function isVigente(): bool
    {
        return in_array($this->status_id, DomainContractStatus::activeStatuses());
    }

    public function isExpired(): bool
    {
        return $this->end_date !== null && $this->end_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if ($this->end_date === null || !$this->isVigente()) {
            return false;
        }

        return $this->end_date->lte(now()->addDays($days)) && $this->end_date->gte(now());
    }

    public function getDaysUntilExpiration(): ?int
    {
        if ($this->end_date === null) {
            return null;
        }

        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->end_date);
    }

    public function getDurationInDays(): ?int
    {
        if ($this->start_date === null || $this->end_date === null) {
            return null;
        }

        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Obter valor mensal estimado baseado na proposta
     */
    public function getMonthlyValueAttribute(): ?float
    {
        return $this->proposal?->price;
    }

    /**
     * Obter valor total estimado do contrato
     */
    public function getTotalValueAttribute(): ?float
    {
        $duration = $this->getDurationInDays();
        $monthlyValue = $this->monthly_value;

        if ($duration === null || $monthlyValue === null) {
            return null;
        }

        // Converter dias para meses (aproximado)
        $months = $duration / 30;
        return $months * $monthlyValue;
    }

    // ==========================================
    // ALERTAS DE RENOVAÇÃO CONFIGURÁVEIS
    // ==========================================

    /**
     * Verificar se está no período de alerta de renovação
     */
    public function isInRenewalAlertPeriod(): bool
    {
        if (!$this->isVigente() || $this->end_date === null) {
            return false;
        }

        $alertDate = $this->end_date->copy()->subDays($this->renewal_alert_days ?? self::ALERT_DAYS_DEFAULT);
        return now()->gte($alertDate) && now()->lte($this->end_date);
    }

    /**
     * Verificar se já foi enviado alerta para este ciclo
     */
    public function hasRecentAlert(): bool
    {
        if ($this->last_renewal_alert_at === null) {
            return false;
        }

        // Considera alerta recente se foi enviado após a data de início do período de alerta
        $alertPeriodStart = $this->end_date->copy()->subDays($this->renewal_alert_days ?? self::ALERT_DAYS_DEFAULT);
        return $this->last_renewal_alert_at->gte($alertPeriodStart);
    }

    /**
     * Verificar se precisa enviar alerta de renovação
     */
    public function needsRenewalAlert(): bool
    {
        return $this->isInRenewalAlertPeriod() && !$this->hasRecentAlert();
    }

    /**
     * Registrar envio de alerta
     */
    public function recordAlertSent(): void
    {
        $this->last_renewal_alert_at = now();
        $this->save();
    }

    /**
     * Obter dias até o vencimento considerando alerta
     */
    public function getDaysUntilAlertAttribute(): ?int
    {
        if ($this->end_date === null) {
            return null;
        }

        $alertDate = $this->end_date->copy()->subDays($this->renewal_alert_days ?? self::ALERT_DAYS_DEFAULT);
        
        if (now()->gt($alertDate)) {
            return 0; // Já passou do período de alerta
        }

        return now()->diffInDays($alertDate);
    }

    // ==========================================
    // RENOVAÇÃO DE CONTRATOS
    // ==========================================

    /**
     * Verificar se é uma renovação
     */
    public function isRenewal(): bool
    {
        return $this->original_contract_id !== null;
    }

    /**
     * Verificar se pode ser renovado
     */
    public function canBeRenewed(): bool
    {
        // Pode renovar se está ativo ou assinado e não expirou há muito tempo
        if (!$this->isVigente()) {
            return false;
        }

        // Permite renovação até 30 dias após o vencimento
        if ($this->end_date && $this->end_date->lt(now()->subDays(30))) {
            return false;
        }

        return true;
    }

    /**
     * Criar contrato de renovação
     */
    public function createRenewal(array $overrides = []): Contract
    {
        if (!$this->canBeRenewed()) {
            throw new \InvalidArgumentException('Contrato não pode ser renovado');
        }

        $duration = $this->getDurationInDays() ?? 365;
        $newStartDate = $this->end_date ?? now();
        $newEndDate = $newStartDate->copy()->addDays($duration);

        $renewalData = array_merge([
            'client_id' => $this->client_id,
            'proposal_id' => $this->proposal_id,
            'status_id' => DomainContractStatus::DRAFT,
            'start_date' => $newStartDate,
            'end_date' => $newEndDate,
            'renewal_alert_days' => $this->renewal_alert_days,
            'auto_renewal' => $this->auto_renewal,
            'original_contract_id' => $this->original_contract_id ?? $this->id,
            'renewal_count' => ($this->renewal_count ?? 0) + 1,
        ], $overrides);

        return self::create($renewalData);
    }

    /**
     * Obter histórico de renovações
     */
    public function getRenewalHistory(): \Illuminate\Database\Eloquent\Collection
    {
        // Se é uma renovação, busca a partir do contrato original
        $originalId = $this->original_contract_id ?? $this->id;

        return self::where(function ($q) use ($originalId) {
            $q->where('id', $originalId)
              ->orWhere('original_contract_id', $originalId);
        })
        ->orderBy('start_date')
        ->get();
    }

    /**
     * Obter contrato mais recente da cadeia de renovações
     */
    public function getLatestRenewal(): Contract
    {
        if (!$this->renewals()->exists()) {
            return $this;
        }

        return $this->renewals()->latest('start_date')->first();
    }

    /**
     * Calcular valor total acumulado em renovações
     */
    public function getTotalRenewalValueAttribute(): float
    {
        return $this->getRenewalHistory()->sum(fn($c) => $c->total_value ?? 0);
    }

    /**
     * Calcular tempo total de relacionamento (soma de todas as renovações)
     */
    public function getTotalRelationshipDaysAttribute(): int
    {
        return $this->getRenewalHistory()->sum(fn($c) => $c->getDurationInDays() ?? 0);
    }

    // ==========================================
    // CONFIGURAÇÃO DE ALERTAS POR TIPO
    // ==========================================

    /**
     * Configurar dias de alerta baseado no tipo de serviço
     */
    public function setAlertDaysFromServiceType(): void
    {
        $serviceType = $this->proposal?->serviceType?->code;

        $this->renewal_alert_days = match ($serviceType) {
            'horista' => self::ALERT_DAYS_MONTHLY,
            'diario' => self::ALERT_DAYS_MONTHLY,
            'mensal' => self::ALERT_DAYS_QUARTERLY,
            default => self::ALERT_DAYS_DEFAULT,
        };
    }

    /**
     * Obter recomendação de dias de alerta baseado na duração
     */
    public static function getRecommendedAlertDays(int $durationDays): int
    {
        return match (true) {
            $durationDays <= 30 => 7,    // Contrato de até 1 mês: 7 dias de alerta
            $durationDays <= 90 => 15,   // Contrato de até 3 meses: 15 dias
            $durationDays <= 180 => 30,  // Contrato de até 6 meses: 30 dias
            $durationDays <= 365 => 45,  // Contrato de até 1 ano: 45 dias
            default => 60,               // Contratos maiores: 60 dias
        };
    }
}
