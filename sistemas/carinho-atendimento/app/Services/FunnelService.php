<?php

namespace App\Services;

use App\Jobs\SyncCrmJob;
use App\Repositories\AtendimentoRepository;
use App\Support\DomainLookup;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
        private AuditService $auditService
    ) {
    }

    public function advanceStatus(
        int $conversationId,
        string $nextStatusCode,
        ?string $priorityCode = null,
        ?int $assignedTo = null,
        ?int $agentId = null,
        ?string $lossReasonCode = null,
        ?string $lossNotes = null
    ): void {
        $conversation = $this->repository->findConversationById($conversationId);

        if (!$conversation) {
            throw new InvalidArgumentException('Conversation not found.');
        }

        $currentStatusCode = DB::table('domain_conversation_status')
            ->where('id', $conversation->status_id)
            ->value('code');

        if (!$currentStatusCode || !$this->isTransitionAllowed($currentStatusCode, $nextStatusCode)) {
            throw new InvalidArgumentException('Invalid funnel transition.');
        }

        $payload = [
            'status_id' => $this->domainLookup->conversationStatusId($nextStatusCode),
            'updated_at' => now()->toDateTimeString(),
        ];

        // Registra mudança de status na auditoria
        $this->auditService->logStatusChange($conversationId, $currentStatusCode, $nextStatusCode, $agentId);

        if ($priorityCode) {
            $currentPriorityCode = DB::table('domain_priority')
                ->where('id', $conversation->priority_id)
                ->value('code');
            
            $payload['priority_id'] = $this->domainLookup->priorityId($priorityCode);
            
            if ($currentPriorityCode !== $priorityCode) {
                $this->auditService->logPriorityChange($conversationId, $currentPriorityCode, $priorityCode, $agentId);
            }
        }

        if ($assignedTo !== null && $assignedTo !== $conversation->assigned_to) {
            $payload['assigned_to'] = $assignedTo;
            $this->auditService->logAssignment($conversationId, $conversation->assigned_to, $assignedTo, $agentId);
        }

        if (in_array($nextStatusCode, ['lost', 'closed'], true)) {
            $payload['closed_at'] = now()->toDateTimeString();
        }

        // Registro de motivo de perda
        if ($nextStatusCode === 'lost') {
            if (!$lossReasonCode) {
                throw new InvalidArgumentException('Loss reason is required when marking as lost.');
            }
            
            $lossReason = $this->domainLookup->lossReasonByCode($lossReasonCode);
            
            if ($lossReason && $lossReason->requires_notes && empty($lossNotes)) {
                throw new InvalidArgumentException('Notes are required for this loss reason.');
            }
            
            $payload['loss_reason_id'] = $this->domainLookup->lossReasonId($lossReasonCode);
            $payload['loss_notes'] = $lossNotes;
        }

        $this->repository->updateConversation($conversationId, $payload);

        if (in_array($nextStatusCode, ['proposal', 'active'], true)) {
            $this->dispatchLeadSync($conversationId);
        }

        if (in_array($nextStatusCode, ['lost', 'closed'], true)) {
            $this->slaService->markResolved($conversationId);
        }

        if ($nextStatusCode === 'lost') {
            $this->dispatchLossSync($conversationId, $lossReasonCode, $lossNotes);
        }

        if ($nextStatusCode === 'closed') {
            $this->dispatchFeedbackRequest($conversationId);
        }
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

    private function dispatchLossSync(int $conversationId, ?string $lossReasonCode, ?string $lossNotes): void
    {
        $conversation = $this->repository->findConversationById($conversationId);

        if (!$conversation) {
            return;
        }

        $contact = $this->repository->findContactById($conversation->contact_id);

        if (!$contact) {
            return;
        }

        SyncCrmJob::dispatch('loss', [
            'conversation_id' => $conversation->id,
            'loss_reason_code' => $lossReasonCode,
            'loss_notes' => $lossNotes,
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->phone,
                'email' => $contact->email,
                'city' => $contact->city,
            ],
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

    /**
     * Retorna os motivos de perda disponíveis
     */
    public function getLossReasons(): array
    {
        return DB::table('domain_loss_reason')
            ->select(['id', 'code', 'label', 'requires_notes'])
            ->get()
            ->toArray();
    }

    /**
     * Retorna estatísticas de perdas por motivo
     */
    public function getLossStats(string $startDate, string $endDate): array
    {
        return DB::table('conversations')
            ->join('domain_loss_reason', 'domain_loss_reason.id', '=', 'conversations.loss_reason_id')
            ->whereBetween('conversations.closed_at', [$startDate, $endDate])
            ->whereNotNull('conversations.loss_reason_id')
            ->groupBy('domain_loss_reason.code', 'domain_loss_reason.label')
            ->select([
                'domain_loss_reason.code',
                'domain_loss_reason.label',
                DB::raw('COUNT(*) as total'),
            ])
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /**
     * Retorna estatísticas do funil
     */
    public function getFunnelStats(string $startDate, string $endDate): array
    {
        $stats = DB::table('conversations')
            ->join('domain_conversation_status', 'domain_conversation_status.id', '=', 'conversations.status_id')
            ->whereBetween('conversations.created_at', [$startDate, $endDate])
            ->groupBy('domain_conversation_status.code', 'domain_conversation_status.label')
            ->select([
                'domain_conversation_status.code',
                'domain_conversation_status.label',
                DB::raw('COUNT(*) as total'),
            ])
            ->get()
            ->toArray();

        $total = array_sum(array_column($stats, 'total'));

        return [
            'stages' => $stats,
            'total' => $total,
            'conversion_rate' => $total > 0 ? round(
                (DB::table('conversations')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status_id', $this->domainLookup->conversationStatusId('active'))
                    ->count() / $total) * 100,
                1
            ) : 0,
        ];
    }
}
