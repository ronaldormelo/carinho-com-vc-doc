<?php

namespace App\Http\Controllers;

use App\Repositories\AtendimentoRepository;
use App\Services\FunnelService;
use App\Services\IncidentService;
use App\Support\DomainLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InboxController extends Controller
{
    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup,
        private FunnelService $funnelService,
        private IncidentService $incidentService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = DB::table('conversations')
            ->join('contacts', 'contacts.id', '=', 'conversations.contact_id')
            ->leftJoin('agents', 'agents.id', '=', 'conversations.assigned_to')
            ->select([
                'conversations.id',
                'conversations.status_id',
                'conversations.priority_id',
                'conversations.assigned_to',
                'conversations.started_at',
                'conversations.closed_at',
                'conversations.updated_at',
                'contacts.name as contact_name',
                'contacts.phone as contact_phone',
                'agents.name as agent_name',
            ]);

        if ($request->filled('status')) {
            $statusId = $this->domainLookup->conversationStatusId((string) $request->input('status'));
            $query->where('conversations.status_id', $statusId);
        }

        if ($request->filled('priority')) {
            $priorityId = $this->domainLookup->priorityId((string) $request->input('priority'));
            $query->where('conversations.priority_id', $priorityId);
        }

        if ($request->filled('assigned_to')) {
            $query->where('conversations.assigned_to', (int) $request->input('assigned_to'));
        }

        $conversations = $query->orderByDesc('conversations.updated_at')->paginate(25);

        return response()->json($conversations);
    }

    public function show(int $conversation): JsonResponse
    {
        $conversationData = $this->repository->findConversationById($conversation);

        if (!$conversationData) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $contact = $this->repository->findContactById($conversationData->contact_id);

        $messages = DB::table('messages')
            ->where('conversation_id', $conversation)
            ->orderBy('id')
            ->get();

        $tags = DB::table('tags')
            ->join('conversation_tags', 'conversation_tags.tag_id', '=', 'tags.id')
            ->where('conversation_tags.conversation_id', $conversation)
            ->select('tags.id', 'tags.name')
            ->orderBy('tags.name')
            ->get();

        return response()->json([
            'conversation' => $conversationData,
            'contact' => $contact,
            'messages' => $messages,
            'tags' => $tags,
        ]);
    }

    public function updateStatus(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'priority' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer'],
        ]);

        $this->funnelService->advanceStatus(
            $conversation,
            $validated['status'],
            $validated['priority'] ?? null,
            $validated['assigned_to'] ?? null
        );

        return response()->json(['status' => 'ok']);
    }

    public function addTags(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'tags' => ['required', 'array'],
            'tags.*' => ['string', 'max:64'],
        ]);

        foreach ($validated['tags'] as $tagName) {
            $tagId = $this->repository->upsertTag($tagName);
            $this->repository->attachTag($conversation, $tagId);
        }

        return response()->json(['status' => 'ok']);
    }

    public function createIncident(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'severity' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $incidentId = $this->incidentService->createIncident(
            $conversation,
            $validated['severity'],
            $validated['notes'] ?? null
        );

        return response()->json(['incident_id' => $incidentId], 201);
    }
}
