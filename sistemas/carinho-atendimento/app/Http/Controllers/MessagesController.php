<?php

namespace App\Http\Controllers;

use App\Repositories\AtendimentoRepository;
use App\Services\InboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessagesController extends Controller
{
    public function __construct(
        private AtendimentoRepository $repository,
        private InboxService $inboxService
    ) {
    }

    public function store(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
            'media_url' => ['nullable', 'string', 'max:512'],
            'direction' => ['nullable', 'in:inbound,outbound'],
        ]);

        $conversationData = $this->repository->findConversationById($conversation);

        if (!$conversationData) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $contact = $this->repository->findContactById($conversationData->contact_id);

        if (!$contact) {
            return response()->json(['message' => 'Contact not found'], 404);
        }

        $direction = $validated['direction'] ?? 'outbound';

        if ($direction === 'inbound') {
            $domainLookup = app(\App\Support\DomainLookup::class);
            $messageId = $this->repository->createMessage([
                'conversation_id' => $conversation,
                'direction_id' => $domainLookup->messageDirectionId('inbound'),
                'body' => $validated['body'],
                'media_url' => $validated['media_url'] ?? null,
                'sent_at' => now()->toDateTimeString(),
                'status_id' => $domainLookup->messageStatusId('delivered'),
            ]);

            return response()->json(['message_id' => $messageId], 201);
        }

        $messageId = $this->inboxService->queueOutboundMessage(
            $conversation,
            $contact->phone,
            $validated['body'],
            $validated['media_url'] ?? null
        );

        return response()->json(['message_id' => $messageId], 201);
    }
}
