<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Exceção operacional com workflow de aprovação.
 * 
 * Registra situações que fogem do padrão operacional e requerem
 * aprovação de supervisor conforme práticas de controle.
 *
 * @property int $id
 * @property string $exception_type
 * @property string $entity_type
 * @property int $entity_id
 * @property ?int $requested_by
 * @property string $description
 * @property string $status
 * @property ?int $approved_by
 * @property ?string $approval_notes
 * @property string $requested_at
 * @property ?string $resolved_at
 */
class OperationalException extends Model
{
    protected $table = 'operational_exceptions';
    
    public $timestamps = false;

    protected $fillable = [
        'exception_type',
        'entity_type',
        'entity_id',
        'requested_by',
        'description',
        'status',
        'approved_by',
        'approval_notes',
        'requested_at',
        'resolved_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Tipos de exceção
    const TYPE_LATE_CHECKIN = 'late_checkin';
    const TYPE_EARLY_CHECKOUT = 'early_checkout';
    const TYPE_SCHEDULE_CHANGE = 'schedule_change';
    const TYPE_MANUAL_ASSIGNMENT = 'manual_assignment';
    const TYPE_FEE_WAIVER = 'fee_waiver';
    const TYPE_POLICY_OVERRIDE = 'policy_override';

    // Status
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Verifica se está pendente.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verifica se foi aprovada.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Verifica se foi rejeitada.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Aprova a exceção.
     */
    public function approve(int $approvedBy, ?string $notes = null): self
    {
        $this->status = self::STATUS_APPROVED;
        $this->approved_by = $approvedBy;
        $this->approval_notes = $notes;
        $this->resolved_at = now();
        $this->save();

        return $this;
    }

    /**
     * Rejeita a exceção.
     */
    public function reject(int $rejectedBy, ?string $notes = null): self
    {
        $this->status = self::STATUS_REJECTED;
        $this->approved_by = $rejectedBy;
        $this->approval_notes = $notes;
        $this->resolved_at = now();
        $this->save();

        return $this;
    }

    /**
     * Scope para pendentes.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope por tipo.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('exception_type', $type);
    }

    /**
     * Scope por entidade.
     */
    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    /**
     * Tipos disponíveis.
     */
    public static function availableTypes(): array
    {
        return [
            self::TYPE_LATE_CHECKIN => 'Check-in Atrasado',
            self::TYPE_EARLY_CHECKOUT => 'Check-out Antecipado',
            self::TYPE_SCHEDULE_CHANGE => 'Alteração de Agendamento',
            self::TYPE_MANUAL_ASSIGNMENT => 'Alocação Manual',
            self::TYPE_FEE_WAIVER => 'Isenção de Taxa',
            self::TYPE_POLICY_OVERRIDE => 'Exceção de Política',
        ];
    }
}
