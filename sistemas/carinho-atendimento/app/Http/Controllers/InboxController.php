<?php

namespace App\Http\Controllers;

use App\Repositories\AtendimentoRepository;
use App\Services\ConversationHistoryService;
use App\Services\EscalationService;
use App\Services\FunnelService;
use App\Services\IncidentService;
use App\Services\SatisfactionService;
use App\Support\DomainLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller principal da inbox de atendimento.
 *
 * Gerencia conversas, tags, incidentes e acoes do funil.
 */
class InboxController extends Controller
{
    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup,
        private FunnelService $funnelService,
        private IncidentService $incidentService,
        private ConversationHistoryService $historyService,
        private EscalationService $escalationService,
        private SatisfactionService $satisfactionService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = DB::table('conversations')
            ->join('contacts', 'contacts.id', '=', 'conversations.contact_id')
            ->leftJoin('agents', 'agents.id', '=', 'conversations.assigned_to')
            ->leftJoin('domain_support_level', 'domain_support_level.id', '=', 'conversations.support_level_id')
            ->select([
                'conversations.id',
                'conversations.status_id',
                'conversations.priority_id',
                'conversations.support_level_id',
                'conversations.assigned_to',
                'conversations.started_at',
                'conversations.closed_at',
                'conversations.updated_at',
                'contacts.name as contact_name',
                'contacts.phone as contact_phone',
                'agents.name as agent_name',
                'domain_support_level.code as support_level',
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

        if ($request->filled('support_level')) {
            $levelId = $this->domainLookup->supportLevelId((string) $request->input('support_level'));
            $query->where('conversations.support_level_id', $levelId);
        }

        $conversations = $query->orderByDesc('conversations.updated_at')->paginate(25);

        return response()->json($conversations);
    }

    public function show(int $conversation): JsonResponse
    {
        $conversationData = $this->repository->findConversationById($conversation);

        if (!$conversationData) {
            return response()->json(['message' => 'Nao encontrada'], 404);
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

        $sla = DB::table('sla_metrics')
            ->where('conversation_id', $conversation)
            ->first();

        $supportLevel = DB::table('domain_support_level')
            ->where('id', $conversationData->support_level_id)
            ->first();

        $lossReason = $conversationData->loss_reason_id
            ? DB::table('domain_loss_reason')->where('id', $conversationData->loss_reason_id)->first()
            : null;

        return response()->json([
            'conversation' => $conversationData,
            'contact' => $contact,
            'messages' => $messages,
            'tags' => $tags,
            'sla' => $sla,
            'support_level' => $supportLevel,
            'loss_reason' => $lossReason,
        ]);
    }

    public function updateStatus(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'priority' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer'],
            'agent_id' => ['nullable', 'integer'],
        ]);

        $this->funnelService->advanceStatus(
            $conversation,
            $validated['status'],
            $validated['priority'] ?? null,
            $validated['assigned_to'] ?? null,
            $validated['agent_id'] ?? null
        );

        return response()->json(['status' => 'ok']);
    }

    /**
     * Marca conversa como perdida com motivo.
     */
    public function markAsLost(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'loss_reason' => ['required', 'string'],
            'loss_notes' => ['nullable', 'string', 'max:1000'],
            'agent_id' => ['nullable', 'integer'],
        ]);

        $this->funnelService->markAsLost(
            $conversation,
            $validated['loss_reason'],
            $validated['loss_notes'] ?? null,
            $validated['agent_id'] ?? null
        );

        return response()->json(['status' => 'ok']);
    }

    public function addTags(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'tags' => ['required', 'array'],
            'tags.*' => ['string', 'max:64'],
            'agent_id' => ['nullable', 'integer'],
        ]);

        foreach ($validated['tags'] as $tagName) {
            $tagId = $this->repository->upsertTag($tagName);
            $this->repository->attachTag($conversation, $tagId);

            $this->historyService->record(
                $conversation,
                'tag',
                $validated['agent_id'] ?? null,
                null,
                $tagName
            );
        }

        return response()->json(['status' => 'ok']);
    }

    public function createIncident(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'severity' => ['required', 'string'],
            'category' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'agent_id' => ['nullable', 'integer'],
        ]);

        $incidentId = $this->incidentService->createIncident(
            $conversation,
            $validated['severity'],
            $validated['category'] ?? null,
            $validated['notes'] ?? null,
            $validated['agent_id'] ?? null
        );

        return response()->json(['incident_id' => $incidentId], 201);
    }

    /**
     * Obtem historico de acoes da conversa.
     */
    public function history(int $conversation): JsonResponse
    {
        return response()->json([
            'history' => $this->historyService->getHistory($conversation),
        ]);
    }

    /**
     * Adiciona nota interna a conversa.
     */
    public function addNote(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
            'agent_id' => ['nullable', 'integer'],
        ]);

        $noteId = $this->historyService->recordNote(
            $conversation,
            $validated['note'],
            $validated['agent_id'] ?? null
        );

        return response()->json(['id' => $noteId], 201);
    }

    /**
     * Escalona conversa para proximo nivel.
     */
    public function escalate(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'agent_id' => ['nullable', 'integer'],
        ]);

        $this->escalationService->escalate(
            $conversation,
            $validated['agent_id'] ?? null,
            $validated['reason'] ?? null
        );

        return response()->json(['status' => 'ok']);
    }

    /**
     * Rebaixa nivel de suporte.
     */
    public function deescalate(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'agent_id' => ['nullable', 'integer'],
        ]);

        $this->escalationService->deescalate(
            $conversation,
            $validated['agent_id'] ?? null,
            $validated['reason'] ?? null
        );

        return response()->json(['status' => 'ok']);
    }

    /**
     * Registra resposta de satisfacao.
     */
    public function recordSatisfaction(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string', 'max:1000'],
        ]);

        $success = $this->satisfactionService->recordResponse(
            $conversation,
            $validated['score'],
            $validated['feedback'] ?? null
        );

        if (!$success) {
            return response()->json(['message' => 'Erro ao registrar'], 422);
        }

        return response()->json(['status' => 'ok']);
    }
}
