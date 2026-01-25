<?php

namespace App\Services;

use App\Models\OperationalAuditTrail;
use App\Models\OperationalException;
use App\Models\DomainAuditAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

/**
 * Service para auditoria operacional.
 * 
 * Gerencia a trilha de auditoria e exceções operacionais,
 * garantindo rastreabilidade completa das operações.
 */
class AuditService
{
    /**
     * Registra uma ação de auditoria.
     */
    public function logAction(
        int $actionId,
        string $entityType,
        int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
        ?int $userId = null,
        string $userType = OperationalAuditTrail::USER_TYPE_SYSTEM
    ): OperationalAuditTrail {
        $request = request();

        $audit = OperationalAuditTrail::create([
            'action_id' => $actionId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'user_type' => $userType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);

        Log::info('Ação auditada', [
            'audit_id' => $audit->id,
            'action_id' => $actionId,
            'entity' => "{$entityType}:{$entityId}",
        ]);

        return $audit;
    }

    /**
     * Registra criação de agendamento.
     */
    public function logScheduleCreated(int $scheduleId, array $data, ?int $userId = null): OperationalAuditTrail
    {
        return $this->logAction(
            DomainAuditAction::SCHEDULE_CREATED,
            'schedule',
            $scheduleId,
            null,
            $data,
            null,
            $userId
        );
    }

    /**
     * Registra atualização de agendamento.
     */
    public function logScheduleUpdated(
        int $scheduleId,
        array $oldData,
        array $newData,
        ?string $reason = null,
        ?int $userId = null
    ): OperationalAuditTrail {
        return $this->logAction(
            DomainAuditAction::SCHEDULE_UPDATED,
            'schedule',
            $scheduleId,
            $oldData,
            $newData,
            $reason,
            $userId
        );
    }

    /**
     * Registra cancelamento de agendamento.
     */
    public function logScheduleCanceled(
        int $scheduleId,
        array $data,
        string $reason,
        ?int $userId = null
    ): OperationalAuditTrail {
        return $this->logAction(
            DomainAuditAction::SCHEDULE_CANCELED,
            'schedule',
            $scheduleId,
            $data,
            null,
            $reason,
            $userId
        );
    }

    /**
     * Registra check-in.
     */
    public function logCheckin(int $scheduleId, array $data, ?int $userId = null): OperationalAuditTrail
    {
        return $this->logAction(
            DomainAuditAction::CHECKIN_PERFORMED,
            'schedule',
            $scheduleId,
            null,
            $data,
            null,
            $userId
        );
    }

    /**
     * Registra check-out.
     */
    public function logCheckout(int $scheduleId, array $data, ?int $userId = null): OperationalAuditTrail
    {
        return $this->logAction(
            DomainAuditAction::CHECKOUT_PERFORMED,
            'schedule',
            $scheduleId,
            null,
            $data,
            null,
            $userId
        );
    }

    /**
     * Registra alocação.
     */
    public function logAssignmentCreated(int $assignmentId, array $data, ?int $userId = null): OperationalAuditTrail
    {
        return $this->logAction(
            DomainAuditAction::ASSIGNMENT_CREATED,
            'assignment',
            $assignmentId,
            null,
            $data,
            null,
            $userId
        );
    }

    /**
     * Registra substituição.
     */
    public function logSubstitution(
        int $assignmentId,
        array $oldData,
        array $newData,
        string $reason,
        ?int $userId = null
    ): OperationalAuditTrail {
        return $this->logAction(
            DomainAuditAction::SUBSTITUTION_PROCESSED,
            'assignment',
            $assignmentId,
            $oldData,
            $newData,
            $reason,
            $userId
        );
    }

    /**
     * Registra emergência.
     */
    public function logEmergencyCreated(int $emergencyId, array $data): OperationalAuditTrail
    {
        return $this->logAction(
            DomainAuditAction::EMERGENCY_CREATED,
            'emergency',
            $emergencyId,
            null,
            $data
        );
    }

    /**
     * Registra resolução de emergência.
     */
    public function logEmergencyResolved(
        int $emergencyId,
        array $data,
        ?string $resolution = null,
        ?int $userId = null
    ): OperationalAuditTrail {
        return $this->logAction(
            DomainAuditAction::EMERGENCY_RESOLVED,
            'emergency',
            $emergencyId,
            null,
            $data,
            $resolution,
            $userId
        );
    }

    /**
     * Registra escalonamento de emergência.
     */
    public function logEmergencyEscalated(
        int $emergencyId,
        int $oldSeverity,
        int $newSeverity
    ): OperationalAuditTrail {
        return $this->logAction(
            DomainAuditAction::EMERGENCY_ESCALATED,
            'emergency',
            $emergencyId,
            ['severity_id' => $oldSeverity],
            ['severity_id' => $newSeverity]
        );
    }

    /**
     * Obtém histórico de auditoria de uma entidade.
     */
    public function getEntityHistory(string $entityType, int $entityId): Collection
    {
        return OperationalAuditTrail::forEntity($entityType, $entityId)
            ->orderBy('created_at', 'desc')
            ->with('action')
            ->get();
    }

    /**
     * Obtém histórico de ações de um usuário.
     */
    public function getUserHistory(int $userId, int $limit = 100): Collection
    {
        return OperationalAuditTrail::byUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->with('action')
            ->get();
    }

    /**
     * Obtém auditoria por período.
     */
    public function getAuditByPeriod(
        string $startDate,
        string $endDate,
        ?int $actionId = null
    ): Collection {
        $query = OperationalAuditTrail::inPeriod($startDate, $endDate)
            ->orderBy('created_at', 'desc')
            ->with('action');

        if ($actionId) {
            $query->byAction($actionId);
        }

        return $query->get();
    }

    /**
     * Cria uma exceção operacional.
     */
    public function createException(
        string $exceptionType,
        string $entityType,
        int $entityId,
        string $description,
        ?int $requestedBy = null
    ): OperationalException {
        $exception = OperationalException::create([
            'exception_type' => $exceptionType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'requested_by' => $requestedBy,
            'description' => $description,
            'status' => OperationalException::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        Log::info('Exceção operacional criada', [
            'exception_id' => $exception->id,
            'type' => $exceptionType,
            'entity' => "{$entityType}:{$entityId}",
        ]);

        return $exception;
    }

    /**
     * Aprova uma exceção operacional.
     */
    public function approveException(
        OperationalException $exception,
        int $approvedBy,
        ?string $notes = null
    ): OperationalException {
        $exception->approve($approvedBy, $notes);

        // Registra na auditoria
        $this->logAction(
            DomainAuditAction::EXCEPTION_APPROVED,
            'exception',
            $exception->id,
            ['status' => OperationalException::STATUS_PENDING],
            ['status' => OperationalException::STATUS_APPROVED, 'notes' => $notes],
            null,
            $approvedBy,
            OperationalAuditTrail::USER_TYPE_SUPERVISOR
        );

        Log::info('Exceção aprovada', [
            'exception_id' => $exception->id,
            'approved_by' => $approvedBy,
        ]);

        return $exception;
    }

    /**
     * Rejeita uma exceção operacional.
     */
    public function rejectException(
        OperationalException $exception,
        int $rejectedBy,
        ?string $notes = null
    ): OperationalException {
        $exception->reject($rejectedBy, $notes);

        // Registra na auditoria
        $this->logAction(
            DomainAuditAction::EXCEPTION_REJECTED,
            'exception',
            $exception->id,
            ['status' => OperationalException::STATUS_PENDING],
            ['status' => OperationalException::STATUS_REJECTED, 'notes' => $notes],
            null,
            $rejectedBy,
            OperationalAuditTrail::USER_TYPE_SUPERVISOR
        );

        Log::info('Exceção rejeitada', [
            'exception_id' => $exception->id,
            'rejected_by' => $rejectedBy,
        ]);

        return $exception;
    }

    /**
     * Obtém exceções pendentes.
     */
    public function getPendingExceptions(): Collection
    {
        return OperationalException::pending()
            ->orderBy('requested_at')
            ->get();
    }

    /**
     * Obtém estatísticas de auditoria.
     */
    public function getAuditStats(?string $startDate = null, ?string $endDate = null): array
    {
        $query = OperationalAuditTrail::query();

        if ($startDate && $endDate) {
            $query->inPeriod($startDate, $endDate);
        }

        $total = $query->count();

        $byAction = $query->select('action_id')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('action_id')
            ->pluck('count', 'action_id')
            ->toArray();

        $byUserType = $query->select('user_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('user_type')
            ->pluck('count', 'user_type')
            ->toArray();

        return [
            'total' => $total,
            'by_action' => $byAction,
            'by_user_type' => $byUserType,
        ];
    }
}
