<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessageJob;
use App\Jobs\SyncAutomationJob;
use App\Repositories\AtendimentoRepository;
use App\Support\DomainLookup;
use App\Integrations\WhatsApp\ZApiClient;

class InboxService
{
    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup,
        private SlaService $slaService,
        private MessageAutomationService $automationService,
        private ZApiClient $whatsappClient
    ) {
    }

    public function handleInboundMessage(array $payload): array
    {
        $normalized = $this->whatsappClient->normalizeInbound($payload);
        $phone = $normalized['phone'] ?? '';

        if ($phone === '') {
            throw new \InvalidArgumentException('Inbound message without phone.');
        }

        $now = now()->toDateTimeString();
        $contact = $this->repository->findContactByPhone($phone);

        if (!$contact) {
            $contactId = $this->repository->createContact([
                'name' => $normalized['name'] ?: 'Contato WhatsApp',
                'phone' => $phone,
                'email' => null,
                'city' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $contact = $this->repository->findContactById($contactId);
        } elseif ($normalized['name'] && $contact->name !== $normalized['name']) {
            $this->repository->updateContact($contact->id, [
                'name' => $normalized['name'],
                'updated_at' => $now,
            ]);
        }

        $conversation = $this->repository->findOpenConversation($contact->id);
        $isNewConversation = false;

        if (!$conversation) {
            $conversationId = $this->repository->createConversation([
                'contact_id' => $contact->id,
                'channel_id' => $this->domainLookup->channelId('whatsapp'),
                'status_id' => $this->domainLookup->conversationStatusId('new'),
                'priority_id' => $this->domainLookup->priorityId('normal'),
                'assigned_to' => null,
                'started_at' => $now,
                'closed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $conversation = $this->repository->findConversationById($conversationId);
            $isNewConversation = true;
        } else {
            $this->repository->updateConversation($conversation->id, [
                'updated_at' => $now,
            ]);
        }

        $messageId = $this->repository->createMessage([
            'conversation_id' => $conversation->id,
            'direction_id' => $this->domainLookup->messageDirectionId('inbound'),
            'body' => $normalized['body'] ?? '',
            'media_url' => $normalized['media_url'] ?? null,
            'sent_at' => $now,
            'status_id' => $this->domainLookup->messageStatusId('delivered'),
        ]);

        $this->repository->createSlaMetricIfMissing($conversation->id);
        $this->slaService->recordInbound($conversation->id);
        $this->automationService->handleInboundAutoReplies($conversation->id, $phone, $isNewConversation);

        if ($isNewConversation) {
            SyncAutomationJob::dispatch('lead_received', [
                'conversation_id' => $conversation->id,
                'contact' => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'phone' => $contact->phone,
                    'email' => $contact->email,
                    'city' => $contact->city,
                ],
                'message' => [
                    'id' => $messageId,
                    'body' => $normalized['body'] ?? '',
                    'media_url' => $normalized['media_url'] ?? null,
                ],
            ]);
        }

        return [
            'conversation_id' => $conversation->id,
            'message_id' => $messageId,
            'contact_id' => $contact->id,
        ];
    }

    public function queueOutboundMessage(int $conversationId, string $phone, string $body, ?string $mediaUrl = null): int
    {
        $messageId = $this->repository->createMessage([
            'conversation_id' => $conversationId,
            'direction_id' => $this->domainLookup->messageDirectionId('outbound'),
            'body' => $body,
            'media_url' => $mediaUrl,
            'sent_at' => null,
            'status_id' => $this->domainLookup->messageStatusId('queued'),
        ]);

        SendWhatsAppMessageJob::dispatch($conversationId, $messageId, $phone, $body, $mediaUrl);

        return $messageId;
    }
}
