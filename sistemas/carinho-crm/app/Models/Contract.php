<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasAuditLog;
use App\Models\Domain\DomainContractStatus;

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
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Campos auditados
    protected array $audited = ['status_id', 'signed_at', 'start_date', 'end_date'];
    protected string $logName = 'contracts';

    // Relacionamentos
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

    // Métodos de negócio
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
}
