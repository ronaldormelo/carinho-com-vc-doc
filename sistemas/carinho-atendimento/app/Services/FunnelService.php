<?php

namespace App\Services;

use App\Jobs\SyncCrmJob;
use App\Repositories\AtendimentoRepository;
use App\Support\DomainLookup;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Servico de gestao do funil de atendimento.
 *
 * Controla as transicoes de status das conversas seguindo
 * o fluxo padrao: new -> triage -> proposal -> waiting -> active/lost/closed
 *
 * Responsabilidades:
 * - Validar transicoes permitidas
 * - Registrar historico de mudancas
 * - Sincronizar com CRM nos marcos importantes
 * - Registrar motivos de perda
 */
class FunnelService
{
    private array $allowedTransitions = [
        'new' => ['triage', 'waiting', 'closed'],
        'triage' => ['proposal', 'waiting', 'lost', 'closed'],
        'proposal' => ['waiting', 'active', 'lost', 'closed'],
        'waiting' => ['proposal', 'active', 'lost', 'closed'],
        'active' => ['closed'],
        'lost' => ['closed'],
        'closed' => [],
    ];

    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup,
        private MessageAutomationService $automationService,
        private SlaService $slaService,
        private ConversationHistoryService $historyService
    ) {
    }

    /**
     * Avanca o status de uma conversa no funil.
     */
    public function advanceStatus(
        int $conversationId,
        string $nextStatusCode,
        ?string $priorityCode = null,
        ?int $assignedTo = null,
        ?int $agentId = null
    ): void {
        $conversation = $this->repository->findConversationById($conversationId);

        if (!$conversation) {
            throw new InvalidArgumentException('Conversa nao encontrada.');
        }

        $currentStatusCode = DB::table('domain_conversation_status')
            ->where('id', $conversation->status_id)
            ->value('code');

        if (!$currentStatusCode || !$this->isTransitionAllowed($currentStatusCode, $nextStatusCode)) {
            throw new InvalidArgumentException('Transicao de funil invalida.');
        }

        $payload = [
            'status_id' => $this->domainLookup->conversationStatusId($nextStatusCode),
            'updated_at' => now()->toDateTimeString(),
        ];

        // Registra mudanca de prioridade se informada
        if ($priorityCode) {
            $oldPriority = DB::table('domain_priority')
                ->where('id', $conversation->priority_id)
                ->value('code');

            $payload['priority_id'] = $this->domainLookup->priorityId($priorityCode);

            if ($oldPriority !== $priorityCode) {
                $this->historyService->recordPriorityChange(
                    $conversationId,
                    $oldPriority,
                    $priorityCode,
                    $agentId
                );
            }
        }

        // Registra atribuicao se informada
        if ($assignedTo !== null && $assignedTo !== $conversation->assigned_to) {
            $payload['assigned_to'] = $assignedTo;
            $this->historyService->recordAssignment(
                $conversationId,
                $conversation->assigned_to,
                $assignedTo,
                $agentId
            );
        }

        if (in_array($nextStatusCode, ['lost', 'closed'], true)) {
            $payload['closed_at'] = now()->toDateTimeString();
        }

        $this->repository->updateConversation($conversationId, $payload);

        // Registra mudanca de status no historico
        $this->historyService->recordStatusChange(
            $conversationId,
            $currentStatusCode,
            $nextStatusCode,
            $agentId
        );

        if (in_array($nextStatusCode, ['proposal', 'active'], true)) {
            $this->dispatchLeadSync($conversationId);
        }

        if (in_array($nextStatusCode, ['lost', 'closed'], true)) {
            $this->slaService->markResolved($conversationId);
        }

        if ($nextStatusCode === 'closed') {
            $this->dispatchFeedbackRequest($conversationId);
        }
    }

    /**
     * Marca uma conversa como perdida com motivo.
     */
    public function markAsLost(
        int $conversationId,
        string $lossReasonCode,
        ?string $lossNotes = null,
        ?int $agentId = null
    ): void {
        $conversation = $this->repository->findConversationById($conversationId);

        if (!$conversation) {
            throw new InvalidArgumentException('Conversa nao encontrada.');
        }

        $currentStatusCode = DB::table('domain_conversation_status')
            ->where('id', $conversation->status_id)
            ->value('code');

        if (!$this->isTransitionAllowed($currentStatusCode, 'lost')) {
            throw new InvalidArgumentException('Nao e possivel marcar como perdido neste status.');
        }

        $lossReasonId = $this->domainLookup->lossReasonId($lossReasonCode);

        $this->repository->updateConversation($conversationId, [
            'status_id' => $this->domainLookup->conversationStatusId('lost'),
            'loss_reason_id' => $lossReasonId,
            'loss_notes' => $lossNotes,
            'closed_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        // Registra encerramento no historico
        $this->historyService->recordClosure(
            $conversationId,
            "lost:{$lossReasonCode}",
            $agentId,
            $lossNotes
        );

        $this->historyService->recordStatusChange(
            $conversationId,
            $currentStatusCode,
            'lost',
            $agentId
        );

        $this->slaService->markResolved($conversationId);

        // Sincroniza perda com CRM
        $this->dispatchLossSync($conversationId, $lossReasonCode, $lossNotes);
    }

    /**
     * Obtem estatisticas de motivos de perda.
     */
    public function getLossReasonStats(string $period = 'month'): array
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $stats = DB::table('conversations')
            ->join('domain_loss_reason', 'domain_loss_reason.id', '=', 'conversations.loss_reason_id')
            ->where('conversations.closed_at', '>=', $startDate)
            ->whereNotNull('conversations.loss_reason_id')
            ->selectRaw('domain_loss_reason.code, domain_loss_reason.label, COUNT(*) as total')
            ->groupBy('domain_loss_reason.code', 'domain_loss_reason.label')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        $total = array_sum(array_column($stats, 'total'));

        return [
            'period' => $period,
            'total_lost' => $total,
            'reasons' => array_map(function ($item) use ($total) {
                return [
                    'code' => $item->code,
                    'label' => $item->label,
                    'count' => $item->total,
                    'percentage' => $total > 0 ? round(($item->total / $total) * 100, 1) : 0,
                ];
            }, $stats),
        ];
    }

    /**
     * Obtem estatisticas do funil.
     */
    public function getFunnelStats(string $period = 'month'): array
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $stats = DB::table('conversations')
            ->join('domain_conversation_status', 'domain_conversation_status.id', '=', 'conversations.status_id')
            ->where('conversations.created_at', '>=', $startDate)
            ->selectRaw('domain_conversation_status.code, domain_conversation_status.label, COUNT(*) as total')
            ->groupBy('domain_conversation_status.code', 'domain_conversation_status.label')
            ->get()
            ->keyBy('code')
            ->toArray();

        $total = array_sum(array_column((array) $stats, 'total'));

        // Calcula taxas de conversao
        $new = $stats['new']->total ?? 0;
        $triage = $stats['triage']->total ?? 0;
        $proposal = $stats['proposal']->total ?? 0;
        $active = $stats['active']->total ?? 0;
        $lost = $stats['lost']->total ?? 0;
        $closed = $stats['closed']->total ?? 0;

        return [
            'period' => $period,
            'total' => $total,
            'by_status' => [
                'new' => $new,
                'triage' => $triage,
                'proposal' => $proposal,
                'waiting' => $stats['waiting']->total ?? 0,
                'active' => $active,
                'lost' => $lost,
                'closed' => $closed,
            ],
            'conversion_rates' => [
                'new_to_triage' => $new > 0 ? round((($triage + $proposal + $active + $lost + $closed) / $new) * 100, 1) : 0,
                'triage_to_proposal' => ($triage + $proposal + $active) > 0 ? round((($proposal + $active) / ($triage + $proposal + $active)) * 100, 1) : 0,
                'proposal_to_active' => ($proposal + $active + $lost) > 0 ? round(($active / ($proposal + $active + $lost)) * 100, 1) : 0,
            ],
            'loss_rate' => $total > 0 ? round(($lost / $total) * 100, 1) : 0,
        ];
    }

    private function isTransitionAllowed(string $current, string $next): bool
    {
        return in_array($next, $this->allowedTransitions[$current] ?? [], true);
    }

    private function dispatchLeadSync(int $conversationId): void
    {
        $conversation = $this->repository->findConversationById($conversationId);

        if (!$conversation) {
            return;
        }

        $contact = $this->repository->findContactById($conversation->contact_id);

        if (!$contact) {
            return;
        }

        SyncCrmJob::dispatch('lead', [
            'conversation_id' => $conversation->id,
            'status_id' => $conversation->status_id,
            'priority_id' => $conversation->priority_id,
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->phone,
                'email' => $contact->email,
                'city' => $contact->city,
            ],
        ]);
    }

    private function dispatchLossSync(int $conversationId, string $lossReason, ?string $notes): void
    {
        $conversation = $this->repository->findConversationById($conversationId);

        if (!$conversation) {
            return;
        }

        $contact = $this->repository->findContactById($conversation->contact_id);

        SyncCrmJob::dispatch('lead_lost', [
            'conversation_id' => $conversationId,
            'loss_reason' => $lossReason,
            'loss_notes' => $notes,
            'contact' => $contact ? [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->phone,
            ] : null,
        ]);
    }

    private function dispatchFeedbackRequest(int $conversationId): void
    {
        $conversation = $this->repository->findConversationById($conversationId);

        if (!$conversation) {
            return;
        }

        $contact = $this->repository->findContactById($conversation->contact_id);

        if (!$contact) {
            return;
        }

        $this->automationService->sendFeedbackRequest($conversationId, $contact->phone);
    }
}
