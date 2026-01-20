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
        private SlaService $slaService
    ) {
    }

    public function advanceStatus(
        int $conversationId,
        string $nextStatusCode,
        ?string $priorityCode = null,
        ?int $assignedTo = null
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

        if ($priorityCode) {
            $payload['priority_id'] = $this->domainLookup->priorityId($priorityCode);
        }

        if ($assignedTo !== null) {
            $payload['assigned_to'] = $assignedTo;
        }

        if (in_array($nextStatusCode, ['lost', 'closed'], true)) {
            $payload['closed_at'] = now()->toDateTimeString();
        }

        $this->repository->updateConversation($conversationId, $payload);

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
