<?php

namespace App\Services;

use App\Repositories\AtendimentoRepository;
use App\Support\DomainLookup;
use Illuminate\Support\Facades\DB;

/**
 * Servico de triagem padronizada para qualificacao de leads.
 *
 * Gerencia o checklist de perguntas obrigatorias e opcionais
 * que devem ser coletadas durante o atendimento inicial.
 */
class TriageService
{
    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup,
        private ConversationHistoryService $historyService
    ) {
    }

    /**
     * Obtem o checklist de triagem ativo.
     */
    public function getChecklist(): array
    {
        return DB::table('triage_checklist')
            ->where('active', 1)
            ->orderBy('item_order')
            ->get()
            ->toArray();
    }

    /**
     * Obtem o status da triagem de uma conversa.
     */
    public function getTriageStatus(int $conversationId): array
    {
        $checklist = $this->getChecklist();
        $responses = $this->getResponses($conversationId);

        $items = [];
        $completedRequired = 0;
        $totalRequired = 0;

        foreach ($checklist as $item) {
            $response = $responses[$item->id] ?? null;

            $items[] = [
                'id' => $item->id,
                'key' => $item->item_key,
                'label' => $item->item_label,
                'required' => (bool) $item->required,
                'response' => $response?->response,
                'completed' => $response !== null && $response->completed_at !== null,
                'completed_at' => $response?->completed_at,
                'completed_by' => $response?->completed_by,
            ];

            if ($item->required) {
                $totalRequired++;
                if ($response && $response->completed_at) {
                    $completedRequired++;
                }
            }
        }

        return [
            'conversation_id' => $conversationId,
            'items' => $items,
            'progress' => [
                'completed' => $completedRequired,
                'total' => $totalRequired,
                'percentage' => $totalRequired > 0 ? round(($completedRequired / $totalRequired) * 100) : 0,
            ],
            'is_complete' => $completedRequired >= $totalRequired,
        ];
    }

    /**
     * Registra resposta de um item da triagem.
     */
    public function saveResponse(
        int $conversationId,
        int $checklistId,
        string $response,
        ?int $agentId = null
    ): int {
        $now = now()->toDateTimeString();

        $existing = DB::table('conversation_triage')
            ->where('conversation_id', $conversationId)
            ->where('checklist_id', $checklistId)
            ->first();

        if ($existing) {
            DB::table('conversation_triage')
                ->where('id', $existing->id)
                ->update([
                    'response' => $response,
                    'completed_at' => $now,
                    'completed_by' => $agentId,
                ]);
            return (int) $existing->id;
        }

        return DB::table('conversation_triage')->insertGetId([
            'conversation_id' => $conversationId,
            'checklist_id' => $checklistId,
            'response' => $response,
            'completed_at' => $now,
            'completed_by' => $agentId,
        ]);
    }

    /**
     * Salva multiplas respostas de uma vez.
     */
    public function saveResponses(int $conversationId, array $responses, ?int $agentId = null): void
    {
        foreach ($responses as $checklistId => $response) {
            if ($response !== null && $response !== '') {
                $this->saveResponse($conversationId, (int) $checklistId, (string) $response, $agentId);
            }
        }

        // Verifica se triagem foi completada
        $status = $this->getTriageStatus($conversationId);

        if ($status['is_complete']) {
            $this->advanceToProposal($conversationId, $agentId);
        }
    }

    /**
     * Gera resumo estruturado da triagem para envio ao CRM.
     */
    public function getTriageSummary(int $conversationId): array
    {
        $responses = DB::table('conversation_triage')
            ->join('triage_checklist', 'triage_checklist.id', '=', 'conversation_triage.checklist_id')
            ->where('conversation_triage.conversation_id', $conversationId)
            ->whereNotNull('conversation_triage.response')
            ->select([
                'triage_checklist.item_key',
                'triage_checklist.item_label',
                'conversation_triage.response',
            ])
            ->get();

        $summary = [];
        foreach ($responses as $item) {
            $summary[$item->item_key] = [
                'label' => $item->item_label,
                'value' => $item->response,
            ];
        }

        return $summary;
    }

    /**
     * Obtem checklist formatado como script para atendente.
     */
    public function getScript(): array
    {
        $checklist = $this->getChecklist();

        $script = [];
        foreach ($checklist as $item) {
            $script[] = [
                'order' => $item->item_order,
                'question' => $this->formatQuestion($item->item_key, $item->item_label),
                'key' => $item->item_key,
                'required' => (bool) $item->required,
                'tips' => $this->getQuestionTips($item->item_key),
            ];
        }

        return $script;
    }

    private function getResponses(int $conversationId): array
    {
        return DB::table('conversation_triage')
            ->where('conversation_id', $conversationId)
            ->get()
            ->keyBy('checklist_id')
            ->toArray();
    }

    private function advanceToProposal(int $conversationId, ?int $agentId): void
    {
        $conversation = $this->repository->findConversationById($conversationId);

        if (!$conversation) {
            return;
        }

        $currentStatus = DB::table('domain_conversation_status')
            ->where('id', $conversation->status_id)
            ->value('code');

        // Só avança se estiver em triagem
        if ($currentStatus === 'triage') {
            $newStatusId = $this->domainLookup->conversationStatusId('proposal');

            $this->repository->updateConversation($conversationId, [
                'status_id' => $newStatusId,
                'updated_at' => now()->toDateTimeString(),
            ]);

            $this->historyService->recordStatusChange(
                $conversationId,
                'triage',
                'proposal',
                $agentId
            );
        }
    }

    private function formatQuestion(string $key, string $label): string
    {
        $questions = [
            'patient_name' => 'Qual o nome completo do paciente que sera cuidado?',
            'patient_age' => 'Qual a idade do paciente?',
            'care_type' => 'Que tipo de cuidado o paciente precisa? (Companhia, higiene, medicacao, etc)',
            'location' => 'Em qual cidade e bairro sera o atendimento?',
            'schedule' => 'Qual o horario ou turno de preferencia? (Manha, tarde, noite, integral)',
            'start_date' => 'Para quando precisa iniciar o servico?',
            'special_needs' => 'O paciente possui alguma necessidade especial ou condicao de saude?',
            'budget' => 'Qual sua expectativa de investimento mensal?',
            'decision_maker' => 'Quem vai decidir sobre a contratacao?',
            'how_found_us' => 'Como voce conheceu a Carinho?',
        ];

        return $questions[$key] ?? $label;
    }

    private function getQuestionTips(string $key): ?string
    {
        $tips = [
            'patient_name' => 'Confirme a escrita correta do nome.',
            'patient_age' => 'Importante para definir perfil do cuidador.',
            'care_type' => 'Detalhe as atividades esperadas do cuidador.',
            'location' => 'Verificar se atendemos a regiao.',
            'schedule' => 'Pergunte tambem sobre finais de semana.',
            'start_date' => 'Urgencia influencia disponibilidade.',
            'special_needs' => 'Alzheimer, mobilidade reduzida, sondas, etc.',
            'budget' => 'Nao pressione, deixe o cliente confortavel.',
            'decision_maker' => 'Ajuda a entender o processo de decisao.',
            'how_found_us' => 'Informacao para marketing.',
        ];

        return $tips[$key] ?? null;
    }
}
