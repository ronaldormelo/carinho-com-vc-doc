<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trilha de auditoria operacional.
 * 
 * Registra todas as ações críticas realizadas no sistema para garantir
 * rastreabilidade completa e conformidade com práticas de mercado.
 *
 * @property int $id
 * @property int $action_id
 * @property string $entity_type
 * @property int $entity_id
 * @property ?int $user_id
 * @property string $user_type
 * @property ?array $old_values
 * @property ?array $new_values
 * @property ?string $reason
 * @property ?string $ip_address
 * @property ?string $user_agent
 * @property string $created_at
 */
class OperationalAuditTrail extends Model
{
    protected $table = 'operational_audit_trail';
    
    public $timestamps = false;

    protected $fillable = [
        'action_id',
        'entity_type',
        'entity_id',
        'user_id',
        'user_type',
        'old_values',
        'new_values',
        'reason',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    // Tipos de ação
    const ACTION_SCHEDULE_CREATED = 1;
    const ACTION_SCHEDULE_UPDATED = 2;
    const ACTION_SCHEDULE_CANCELED = 3;
    const ACTION_CHECKIN_PERFORMED = 4;
    const ACTION_CHECKOUT_PERFORMED = 5;
    const ACTION_ASSIGNMENT_CREATED = 6;
    const ACTION_ASSIGNMENT_CONFIRMED = 7;
    const ACTION_SUBSTITUTION_PROCESSED = 8;
    const ACTION_EMERGENCY_CREATED = 9;
    const ACTION_EMERGENCY_RESOLVED = 10;
    const ACTION_EMERGENCY_ESCALATED = 11;
    const ACTION_EXCEPTION_APPROVED = 12;
    const ACTION_EXCEPTION_REJECTED = 13;
    const ACTION_MANUAL_OVERRIDE = 14;

    // Tipos de usuário
    const USER_TYPE_SYSTEM = 'system';
    const USER_TYPE_OPERATOR = 'operator';
    const USER_TYPE_SUPERVISOR = 'supervisor';

    /**
     * Ação de auditoria.
     */
    public function action(): BelongsTo
    {
        return $this->belongsTo(DomainAuditAction::class, 'action_id');
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
     * Scope por usuário.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope por tipo de ação.
     */
    public function scopeByAction($query, int $actionId)
    {
        return $query->where('action_id', $actionId);
    }

    /**
     * Scope por período.
     */
    public function scopeInPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Obtém descrição legível da ação.
     */
    public function getActionDescriptionAttribute(): string
    {
        return $this->action?->label ?? 'Ação desconhecida';
    }

    /**
     * Obtém resumo das alterações.
     */
    public function getChangesSummaryAttribute(): array
    {
        $changes = [];
        $oldValues = $this->old_values ?? [];
        $newValues = $this->new_values ?? [];

        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        return $changes;
    }
}
