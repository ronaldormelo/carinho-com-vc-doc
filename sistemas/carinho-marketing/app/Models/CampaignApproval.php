<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Domain\DomainApprovalStatus;

/**
 * Model para aprovações de orçamento de campanhas.
 * 
 * Controle de aprovação hierárquica para campanhas com
 * orçamento acima do limite configurado.
 */
class CampaignApproval extends Model
{
    protected $table = 'campaign_approvals';

    protected $fillable = [
        'campaign_id',
        'requested_budget',
        'status_id',
        'requested_by',
        'approved_by',
        'justification',
        'approval_notes',
        'requested_at',
        'decided_at',
    ];

    protected $casts = [
        'requested_budget' => 'decimal:2',
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    /**
     * Relacionamento com campanha.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Relacionamento com status de aprovação.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainApprovalStatus::class, 'status_id');
    }

    /**
     * Verifica se está pendente.
     */
    public function isPending(): bool
    {
        return $this->status_id === DomainApprovalStatus::PENDING;
    }

    /**
     * Verifica se foi aprovada.
     */
    public function isApproved(): bool
    {
        return $this->status_id === DomainApprovalStatus::APPROVED;
    }

    /**
     * Verifica se foi rejeitada.
     */
    public function isRejected(): bool
    {
        return $this->status_id === DomainApprovalStatus::REJECTED;
    }

    /**
     * Scope para aprovações pendentes.
     */
    public function scopePending($query)
    {
        return $query->where('status_id', DomainApprovalStatus::PENDING);
    }

    /**
     * Scope para aprovações aprovadas.
     */
    public function scopeApproved($query)
    {
        return $query->where('status_id', DomainApprovalStatus::APPROVED);
    }

    /**
     * Cria solicitação de aprovação.
     */
    public static function createRequest(
        int $campaignId,
        float $budget,
        int $requestedBy,
        ?string $justification = null
    ): self {
        return self::create([
            'campaign_id' => $campaignId,
            'requested_budget' => $budget,
            'status_id' => DomainApprovalStatus::PENDING,
            'requested_by' => $requestedBy,
            'justification' => $justification,
            'requested_at' => now(),
        ]);
    }

    /**
     * Aprova a solicitação.
     */
    public function approve(int $approvedBy, ?string $notes = null): self
    {
        $this->update([
            'status_id' => DomainApprovalStatus::APPROVED,
            'approved_by' => $approvedBy,
            'approval_notes' => $notes,
            'decided_at' => now(),
        ]);

        return $this->fresh();
    }

    /**
     * Rejeita a solicitação.
     */
    public function reject(int $rejectedBy, ?string $notes = null): self
    {
        $this->update([
            'status_id' => DomainApprovalStatus::REJECTED,
            'approved_by' => $rejectedBy,
            'approval_notes' => $notes,
            'decided_at' => now(),
        ]);

        return $this->fresh();
    }
}
