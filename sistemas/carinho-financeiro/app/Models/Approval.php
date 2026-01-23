<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Modelo de Aprovação.
 *
 * Representa uma solicitação de aprovação para operações
 * que excedem os limites configurados no sistema.
 */
class Approval extends Model
{
    use LogsActivity;

    protected $table = 'approvals';

    protected $fillable = [
        'status_id',
        'operation_type',
        'operation_id',
        'amount',
        'threshold_amount',
        'requested_by',
        'request_reason',
        'requested_at',
        'decided_by',
        'decision_reason',
        'decided_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'threshold_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Tipos de operação
    public const TYPE_DISCOUNT = 'discount';
    public const TYPE_REFUND = 'refund';
    public const TYPE_PAYOUT = 'payout';
    public const TYPE_PAYABLE = 'payable';

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainApprovalStatus::class, 'status_id');
    }

    /**
     * Relacionamento polimórfico com a operação.
     */
    public function operation(): MorphTo
    {
        return $this->morphTo('operation');
    }

    /**
     * Verifica se está pendente.
     */
    public function isPending(): bool
    {
        return $this->status_id === DomainApprovalStatus::PENDING;
    }

    /**
     * Verifica se está aprovado.
     */
    public function isApproved(): bool
    {
        return in_array($this->status_id, [
            DomainApprovalStatus::APPROVED,
            DomainApprovalStatus::AUTO_APPROVED,
        ]);
    }

    /**
     * Verifica se foi rejeitado.
     */
    public function isRejected(): bool
    {
        return $this->status_id === DomainApprovalStatus::REJECTED;
    }

    /**
     * Verifica se expirou.
     */
    public function isExpired(): bool
    {
        return $this->isPending() && $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Aprova a solicitação.
     */
    public function approve(string $decidedBy, ?string $reason = null): self
    {
        $this->status_id = DomainApprovalStatus::APPROVED;
        $this->decided_by = $decidedBy;
        $this->decision_reason = $reason;
        $this->decided_at = now();
        $this->save();

        return $this;
    }

    /**
     * Rejeita a solicitação.
     */
    public function reject(string $decidedBy, string $reason): self
    {
        $this->status_id = DomainApprovalStatus::REJECTED;
        $this->decided_by = $decidedBy;
        $this->decision_reason = $reason;
        $this->decided_at = now();
        $this->save();

        return $this;
    }

    /**
     * Scope para pendentes.
     */
    public function scopePending($query)
    {
        return $query->where('status_id', DomainApprovalStatus::PENDING);
    }

    /**
     * Scope para aprovados.
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('status_id', [
            DomainApprovalStatus::APPROVED,
            DomainApprovalStatus::AUTO_APPROVED,
        ]);
    }

    /**
     * Scope por tipo de operação.
     */
    public function scopeForOperation($query, string $type)
    {
        return $query->where('operation_type', $type);
    }

    /**
     * Scope para não expirados.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Configurações de log de atividade.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status_id', 'decided_by', 'decision_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
