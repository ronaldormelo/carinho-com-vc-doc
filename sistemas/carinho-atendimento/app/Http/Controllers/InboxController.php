<?php

namespace App\Http\Controllers;

use App\Repositories\AtendimentoRepository;
use App\Services\AuditService;
use App\Services\EscalationService;
use App\Services\FunnelService;
use App\Services\IncidentService;
use App\Services\NoteService;
use App\Services\ScriptService;
use App\Services\SlaService;
use App\Services\TriageService;
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
        private IncidentService $incidentService,
        private TriageService $triageService,
        private ScriptService $scriptService,
        private SlaService $slaService,
        private AuditService $auditService,
        private NoteService $noteService,
        private EscalationService $escalationService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = DB::table('conversations')
            ->join('contacts', 'contacts.id', '=', 'conversations.contact_id')
            ->join('domain_conversation_status', 'domain_conversation_status.id', '=', 'conversations.status_id')
            ->join('domain_priority', 'domain_priority.id', '=', 'conversations.priority_id')
            ->join('domain_support_level', 'domain_support_level.id', '=', 'conversations.support_level_id')
            ->leftJoin('agents', 'agents.id', '=', 'conversations.assigned_to')
            ->select([
                'conversations.id',
                'conversations.status_id',
                'domain_conversation_status.code as status_code',
                'domain_conversation_status.label as status_label',
                'conversations.priority_id',
                'domain_priority.code as priority_code',
                'domain_priority.label as priority_label',
                'conversations.support_level_id',
                'domain_support_level.code as support_level_code',
                'domain_support_level.label as support_level_label',
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

        if ($request->filled('support_level')) {
            $supportLevelId = $this->domainLookup->supportLevelId((string) $request->input('support_level'));
            $query->where('conversations.support_level_id', $supportLevelId);
        }

        if ($request->filled('assigned_to')) {
            $query->where('conversations.assigned_to', (int) $request->input('assigned_to'));
        }

        if ($request->boolean('open_only')) {
            $query->whereNull('conversations.closed_at');
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
            ->join('domain_message_direction', 'domain_message_direction.id', '=', 'messages.direction_id')
            ->join('domain_message_status', 'domain_message_status.id', '=', 'messages.status_id')
            ->where('conversation_id', $conversation)
            ->select([
                'messages.*',
                'domain_message_direction.code as direction_code',
                'domain_message_status.code as status_code',
            ])
            ->orderBy('messages.id')
            ->get();

        $tags = DB::table('tags')
            ->join('conversation_tags', 'conversation_tags.tag_id', '=', 'tags.id')
            ->where('conversation_tags.conversation_id', $conversation)
            ->select('tags.id', 'tags.name')
            ->orderBy('tags.name')
            ->get();

        // Dados de SLA
        $slaMetrics = $this->slaService->getSlaMetrics($conversation);

        // Dados de triagem
        $triageAnswers = $this->triageService->getTriageAnswers($conversation);
        $triageComplete = $this->triageService->isTriageComplete($conversation);

        // Notas
        $notes = $this->noteService->getNotes($conversation);

        // Histórico de ações
        $actionHistory = $this->auditService->getConversationHistory($conversation);

        // Histórico de escalonamentos
        $escalationHistory = $this->escalationService->getEscalationHistory($conversation);

        // Status e prioridade
        $status = DB::table('domain_conversation_status')
            ->where('id', $conversationData->status_id)
            ->first();
        
        $priority = DB::table('domain_priority')
            ->where('id', $conversationData->priority_id)
            ->first();

        $supportLevel = DB::table('domain_support_level')
            ->where('id', $conversationData->support_level_id ?? 1)
            ->first();

        return response()->json([
            'conversation' => $conversationData,
            'contact' => $contact,
            'messages' => $messages,
            'tags' => $tags,
            'status' => $status,
            'priority' => $priority,
            'support_level' => $supportLevel,
            'sla_metrics' => $slaMetrics,
            'triage' => [
                'answers' => $triageAnswers,
                'is_complete' => $triageComplete,
            ],
            'notes' => $notes,
            'action_history' => $actionHistory,
            'escalation_history' => $escalationHistory,
        ]);
    }

    public function updateStatus(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'priority' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer'],
            'agent_id' => ['nullable', 'integer'],
            'loss_reason' => ['nullable', 'string'],
            'loss_notes' => ['nullable', 'string'],
        ]);

        $this->funnelService->advanceStatus(
            $conversation,
            $validated['status'],
            $validated['priority'] ?? null,
            $validated['assigned_to'] ?? null,
            $validated['agent_id'] ?? null,
            $validated['loss_reason'] ?? null,
            $validated['loss_notes'] ?? null
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
            
            $this->auditService->logAction(
                $conversation,
                'tag_added',
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
            'notes' => ['nullable', 'string'],
        ]);

        $incidentId = $this->incidentService->createIncident(
            $conversation,
            $validated['severity'],
            $validated['notes'] ?? null
        );

        return response()->json(['incident_id' => $incidentId], 201);
    }

    /**
     * Salva as respostas do checklist de triagem
     */
    public function saveTriage(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'agent_id' => ['nullable', 'integer'],
        ]);

        $this->triageService->saveTriageAnswers(
            $conversation,
            $validated['answers'],
            $validated['agent_id'] ?? null
        );

        // Se a triagem está completa, calcula e atualiza a prioridade
        if ($this->triageService->isTriageComplete($conversation)) {
            $urgency = $this->triageService->calculateUrgency($conversation);
            
            $conversationData = $this->repository->findConversationById($conversation);
            $currentPriorityCode = DB::table('domain_priority')
                ->where('id', $conversationData->priority_id)
                ->value('code');

            if ($currentPriorityCode !== $urgency) {
                $this->repository->updateConversation($conversation, [
                    'priority_id' => $this->domainLookup->priorityId($urgency),
                    'updated_at' => now()->toDateTimeString(),
                ]);
                $this->auditService->logPriorityChange(
                    $conversation,
                    $currentPriorityCode,
                    $urgency,
                    $validated['agent_id'] ?? null
                );
            }
        }

        return response()->json([
            'status' => 'ok',
            'is_complete' => $this->triageService->isTriageComplete($conversation),
        ]);
    }

    /**
     * Retorna os itens do checklist de triagem
     */
    public function getTriageChecklist(): JsonResponse
    {
        $items = $this->triageService->getChecklistItems();
        return response()->json(['items' => $items]);
    }

    /**
     * Adiciona uma nota à conversa
     */
    public function addNote(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'agent_id' => ['required', 'integer'],
            'is_private' => ['nullable', 'boolean'],
        ]);

        $noteId = $this->noteService->addNote(
            $conversation,
            $validated['agent_id'],
            $validated['content'],
            $validated['is_private'] ?? true
        );

        return response()->json(['note_id' => $noteId], 201);
    }

    /**
     * Escalona a conversa para o próximo nível de suporte
     */
    public function escalate(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string'],
            'from_agent_id' => ['nullable', 'integer'],
            'to_agent_id' => ['nullable', 'integer'],
        ]);

        $success = $this->escalationService->escalate(
            $conversation,
            $validated['reason'],
            $validated['from_agent_id'] ?? null,
            $validated['to_agent_id'] ?? null
        );

        if (!$success) {
            return response()->json(['message' => 'Cannot escalate further'], 400);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Retorna scripts sugeridos para a conversa
     */
    public function getSuggestedScripts(int $conversation): JsonResponse
    {
        $conversationData = $this->repository->findConversationById($conversation);

        if (!$conversationData) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $statusCode = DB::table('domain_conversation_status')
            ->where('id', $conversationData->status_id)
            ->value('code');

        $supportLevelCode = DB::table('domain_support_level')
            ->where('id', $conversationData->support_level_id ?? 1)
            ->value('code');

        $scripts = $this->scriptService->getSuggestedScripts($statusCode, $supportLevelCode);

        return response()->json(['scripts' => $scripts]);
    }

    /**
     * Retorna motivos de perda disponíveis
     */
    public function getLossReasons(): JsonResponse
    {
        $reasons = $this->funnelService->getLossReasons();
        return response()->json(['reasons' => $reasons]);
    }

    /**
     * Retorna conversas em risco de SLA
     */
    public function getAtRisk(): JsonResponse
    {
        $atRisk = $this->slaService->getConversationsAtRisk();
        return response()->json(['conversations' => $atRisk]);
    }

    /**
     * Retorna alertas de SLA pendentes
     */
    public function getSlaAlerts(): JsonResponse
    {
        $alerts = $this->slaService->getPendingAlerts();
        return response()->json(['alerts' => $alerts]);
    }

    /**
     * Reconhece um alerta de SLA
     */
    public function acknowledgeSlaAlert(Request $request, int $alertId): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => ['required', 'integer'],
        ]);

        $this->slaService->acknowledgeAlert($alertId, $validated['agent_id']);

        return response()->json(['status' => 'ok']);
    }
}
