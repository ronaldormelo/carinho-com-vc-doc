<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\DomainApprovalStatus;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Aprovações.
 *
 * Responsável por:
 * - Validar se operações requerem aprovação
 * - Criar solicitações de aprovação
 * - Processar aprovações e rejeições
 * - Verificar expiração de solicitações
 *
 * Práticas de controle interno para operações sensíveis:
 * - Descontos acima do limite
 * - Reembolsos acima do limite
 * - Repasses acima do limite
 * - Pagamentos de contas acima do limite
 */
class ApprovalService
{
    public function __construct(
        protected SettingService $settingService
    ) {}

    /**
     * Verifica se uma operação requer aprovação.
     */
    public function requiresApproval(string $operationType, float $amount): array
    {
        $thresholdKey = match ($operationType) {
            Approval::TYPE_DISCOUNT => 'approval_discount_threshold',
            Approval::TYPE_REFUND => 'approval_refund_threshold',
            Approval::TYPE_PAYOUT => 'approval_payout_threshold',
            Approval::TYPE_PAYABLE => 'approval_payable_threshold',
            default => null,
        };

        if (!$thresholdKey) {
            return [
                'requires' => false,
                'reason' => 'Tipo de operação não configurado para aprovação',
            ];
        }

        $threshold = $this->settingService->get($thresholdKey, PHP_FLOAT_MAX);

        // Para descontos, o threshold é percentual
        if ($operationType === Approval::TYPE_DISCOUNT) {
            $requires = $amount > $threshold;
            return [
                'requires' => $requires,
                'threshold' => $threshold,
                'threshold_unit' => '%',
                'reason' => $requires 
                    ? "Desconto de {$amount}% excede limite de {$threshold}%"
                    : null,
            ];
        }

        // Para valores monetários
        $requires = $amount > $threshold;
        return [
            'requires' => $requires,
            'threshold' => $threshold,
            'threshold_unit' => 'R$',
            'reason' => $requires 
                ? "Valor R$ " . number_format($amount, 2, ',', '.') . " excede limite de R$ " . number_format($threshold, 2, ',', '.')
                : null,
        ];
    }

    /**
     * Cria solicitação de aprovação.
     */
    public function createApprovalRequest(
        string $operationType,
        int $operationId,
        float $amount,
        string $requestedBy,
        ?string $reason = null
    ): Approval {
        $check = $this->requiresApproval($operationType, $amount);

        if (!$check['requires']) {
            // Auto-aprova se não requer aprovação manual
            return $this->createAutoApproval($operationType, $operationId, $amount, $requestedBy);
        }

        $expirationHours = $this->settingService->get('approval_expiration_hours', 48);

        $approval = Approval::create([
            'status_id' => DomainApprovalStatus::PENDING,
            'operation_type' => $operationType,
            'operation_id' => $operationId,
            'amount' => $amount,
            'threshold_amount' => $check['threshold'],
            'requested_by' => $requestedBy,
            'request_reason' => $reason,
            'requested_at' => now(),
            'expires_at' => now()->addHours($expirationHours),
        ]);

        Log::info('Solicitação de aprovação criada', [
            'approval_id' => $approval->id,
            'operation_type' => $operationType,
            'operation_id' => $operationId,
            'amount' => $amount,
            'requested_by' => $requestedBy,
        ]);

        return $approval;
    }

    /**
     * Cria aprovação automática (quando dentro do limite).
     */
    protected function createAutoApproval(
        string $operationType,
        int $operationId,
        float $amount,
        string $requestedBy
    ): Approval {
        $approval = Approval::create([
            'status_id' => DomainApprovalStatus::AUTO_APPROVED,
            'operation_type' => $operationType,
            'operation_id' => $operationId,
            'amount' => $amount,
            'threshold_amount' => 0,
            'requested_by' => $requestedBy,
            'request_reason' => 'Dentro do limite de aprovação automática',
            'requested_at' => now(),
            'decided_by' => 'SISTEMA',
            'decision_reason' => 'Aprovado automaticamente - valor dentro do limite',
            'decided_at' => now(),
        ]);

        Log::info('Aprovação automática', [
            'approval_id' => $approval->id,
            'operation_type' => $operationType,
            'amount' => $amount,
        ]);

        return $approval;
    }

    /**
     * Aprova uma solicitação.
     */
    public function approve(int $approvalId, string $decidedBy, ?string $reason = null): Approval
    {
        $approval = Approval::findOrFail($approvalId);

        if (!$approval->isPending()) {
            throw new \Exception('Esta solicitação já foi processada');
        }

        if ($approval->isExpired()) {
            throw new \Exception('Esta solicitação expirou');
        }

        $approval->approve($decidedBy, $reason);

        Log::info('Solicitação aprovada', [
            'approval_id' => $approval->id,
            'decided_by' => $decidedBy,
            'reason' => $reason,
        ]);

        return $approval;
    }

    /**
     * Rejeita uma solicitação.
     */
    public function reject(int $approvalId, string $decidedBy, string $reason): Approval
    {
        $approval = Approval::findOrFail($approvalId);

        if (!$approval->isPending()) {
            throw new \Exception('Esta solicitação já foi processada');
        }

        $approval->reject($decidedBy, $reason);

        Log::info('Solicitação rejeitada', [
            'approval_id' => $approval->id,
            'decided_by' => $decidedBy,
            'reason' => $reason,
        ]);

        return $approval;
    }

    /**
     * Verifica se operação está aprovada.
     */
    public function isOperationApproved(string $operationType, int $operationId): bool
    {
        return Approval::where('operation_type', $operationType)
            ->where('operation_id', $operationId)
            ->approved()
            ->exists();
    }

    /**
     * Obtém status de aprovação de uma operação.
     */
    public function getOperationApprovalStatus(string $operationType, int $operationId): ?array
    {
        $approval = Approval::where('operation_type', $operationType)
            ->where('operation_id', $operationId)
            ->latest()
            ->first();

        if (!$approval) {
            return null;
        }

        return [
            'id' => $approval->id,
            'status' => $approval->status->code,
            'status_label' => $approval->status->label,
            'is_approved' => $approval->isApproved(),
            'is_pending' => $approval->isPending(),
            'is_rejected' => $approval->isRejected(),
            'is_expired' => $approval->isExpired(),
            'requested_by' => $approval->requested_by,
            'requested_at' => $approval->requested_at?->toDateTimeString(),
            'decided_by' => $approval->decided_by,
            'decided_at' => $approval->decided_at?->toDateTimeString(),
            'expires_at' => $approval->expires_at?->toDateTimeString(),
        ];
    }

    /**
     * Lista solicitações pendentes.
     */
    public function getPendingApprovals(?string $operationType = null): array
    {
        $query = Approval::pending()
            ->notExpired()
            ->with('status')
            ->orderBy('requested_at', 'asc');

        if ($operationType) {
            $query->forOperation($operationType);
        }

        return $query->get()
            ->map(function ($approval) {
                return [
                    'id' => $approval->id,
                    'operation_type' => $approval->operation_type,
                    'operation_id' => $approval->operation_id,
                    'amount' => $approval->amount,
                    'threshold' => $approval->threshold_amount,
                    'requested_by' => $approval->requested_by,
                    'request_reason' => $approval->request_reason,
                    'requested_at' => $approval->requested_at->toDateTimeString(),
                    'expires_at' => $approval->expires_at?->toDateTimeString(),
                    'hours_until_expiration' => $approval->expires_at 
                        ? round($approval->expires_at->diffInHours(now(), false), 1)
                        : null,
                ];
            })
            ->toArray();
    }

    /**
     * Processa solicitações expiradas.
     */
    public function processExpiredApprovals(): int
    {
        $expired = Approval::pending()
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expired as $approval) {
            $approval->reject('SISTEMA', 'Solicitação expirada automaticamente');
            $count++;

            Log::info('Aprovação expirada', [
                'approval_id' => $approval->id,
            ]);
        }

        return $count;
    }

    /**
     * Obtém métricas de aprovação.
     */
    public function getApprovalMetrics(int $daysBack = 30): array
    {
        $startDate = now()->subDays($daysBack);

        $approvals = Approval::where('requested_at', '>=', $startDate)->get();

        $total = $approvals->count();
        $approved = $approvals->whereIn('status_id', [
            DomainApprovalStatus::APPROVED,
            DomainApprovalStatus::AUTO_APPROVED,
        ])->count();
        $rejected = $approvals->where('status_id', DomainApprovalStatus::REJECTED)->count();
        $pending = $approvals->where('status_id', DomainApprovalStatus::PENDING)->count();
        $autoApproved = $approvals->where('status_id', DomainApprovalStatus::AUTO_APPROVED)->count();

        // Tempo médio de decisão
        $avgDecisionTime = $approvals
            ->whereNotNull('decided_at')
            ->avg(fn($a) => $a->decided_at->diffInHours($a->requested_at));

        // Por tipo de operação
        $byType = $approvals->groupBy('operation_type')
            ->map(fn($group) => [
                'total' => $group->count(),
                'approved' => $group->whereIn('status_id', [
                    DomainApprovalStatus::APPROVED,
                    DomainApprovalStatus::AUTO_APPROVED,
                ])->count(),
                'total_amount' => $group->sum('amount'),
            ])
            ->toArray();

        return [
            'period_days' => $daysBack,
            'totals' => [
                'total' => $total,
                'approved' => $approved,
                'rejected' => $rejected,
                'pending' => $pending,
                'auto_approved' => $autoApproved,
            ],
            'rates' => [
                'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
                'auto_approval_rate' => $total > 0 ? round(($autoApproved / $total) * 100, 2) : 0,
                'rejection_rate' => $total > 0 ? round(($rejected / $total) * 100, 2) : 0,
            ],
            'avg_decision_time_hours' => round($avgDecisionTime ?? 0, 1),
            'by_operation_type' => $byType,
        ];
    }
}
