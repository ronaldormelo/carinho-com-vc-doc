<?php

namespace App\Services;

use App\Jobs\NotifyOperacaoJob;
use App\Jobs\SyncCrmJob;
use App\Repositories\AtendimentoRepository;
use App\Support\DomainLookup;

class IncidentService
{
    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup
    ) {
    }

    public function createIncident(int $conversationId, string $severityCode, ?string $notes = null): int
    {
        $now = now()->toDateTimeString();
        $severityId = $this->domainLookup->incidentSeverityId($severityCode);

        $incidentId = $this->repository->createIncident([
            'conversation_id' => $conversationId,
            'severity_id' => $severityId,
            'notes' => $notes,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $conversation = $this->repository->findConversationById($conversationId);
        $contact = $conversation ? $this->repository->findContactById($conversation->contact_id) : null;

        $payload = [
            'incident_id' => $incidentId,
            'conversation_id' => $conversationId,
            'severity' => $severityCode,
            'notes' => $notes,
            'contact' => $contact ? [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->phone,
                'email' => $contact->email,
                'city' => $contact->city,
            ] : null,
        ];

        if (in_array($severityCode, ['high', 'critical'], true)) {
            NotifyOperacaoJob::dispatch($payload);
            SyncCrmJob::dispatch('incident', $payload);
        }

        return $incidentId;
    }
}
