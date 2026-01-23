<?php

namespace App\Services;

use App\Repositories\AtendimentoRepository;
use App\Support\DomainLookup;
use Illuminate\Support\Facades\DB;

class TriageService
{
    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup
    ) {
    }

    /**
     * Retorna os itens do checklist de triagem ativos
     */
    public function getChecklistItems(): array
    {
        return DB::table('triage_checklist_items')
            ->where('active', 1)
            ->orderBy('display_order')
            ->get()
            ->map(function ($item) {
                $item->options = $item->options_json ? json_decode($item->options_json, true) : null;
                unset($item->options_json);
                return $item;
            })
            ->toArray();
    }

    /**
     * Salva as respostas do checklist de triagem para uma conversa
     */
    public function saveTriageAnswers(int $conversationId, array $answers, ?int $agentId = null): void
    {
        $now = now()->toDateTimeString();

        foreach ($answers as $itemCode => $answer) {
            $item = DB::table('triage_checklist_items')
                ->where('code', $itemCode)
                ->where('active', 1)
                ->first();

            if (!$item) {
                continue;
            }

            // Verifica se já existe uma resposta para este item
            $existing = DB::table('conversation_triage')
                ->where('conversation_id', $conversationId)
                ->where('checklist_item_id', $item->id)
                ->first();

            if ($existing) {
                // Atualiza resposta existente
                DB::table('conversation_triage')
                    ->where('id', $existing->id)
                    ->update([
                        'answer' => is_array($answer) ? json_encode($answer) : $answer,
                        'answered_by' => $agentId,
                    ]);
            } else {
                // Cria nova resposta
                DB::table('conversation_triage')->insert([
                    'conversation_id' => $conversationId,
                    'checklist_item_id' => $item->id,
                    'answer' => is_array($answer) ? json_encode($answer) : $answer,
                    'answered_by' => $agentId,
                    'created_at' => $now,
                ]);
            }
        }
    }

    /**
     * Retorna as respostas do checklist de uma conversa
     */
    public function getTriageAnswers(int $conversationId): array
    {
        return DB::table('conversation_triage')
            ->join('triage_checklist_items', 'triage_checklist_items.id', '=', 'conversation_triage.checklist_item_id')
            ->leftJoin('agents', 'agents.id', '=', 'conversation_triage.answered_by')
            ->where('conversation_triage.conversation_id', $conversationId)
            ->select([
                'triage_checklist_items.code',
                'triage_checklist_items.question',
                'triage_checklist_items.field_type',
                'conversation_triage.answer',
                'conversation_triage.created_at',
                'agents.name as answered_by_name',
            ])
            ->orderBy('triage_checklist_items.display_order')
            ->get()
            ->toArray();
    }

    /**
     * Verifica se a triagem está completa (todos os itens obrigatórios preenchidos)
     */
    public function isTriageComplete(int $conversationId): bool
    {
        $requiredItems = DB::table('triage_checklist_items')
            ->where('active', 1)
            ->where('is_required', 1)
            ->pluck('id')
            ->toArray();

        if (empty($requiredItems)) {
            return true;
        }

        $answeredItems = DB::table('conversation_triage')
            ->where('conversation_id', $conversationId)
            ->whereIn('checklist_item_id', $requiredItems)
            ->whereNotNull('answer')
            ->where('answer', '!=', '')
            ->pluck('checklist_item_id')
            ->toArray();

        return count($answeredItems) >= count($requiredItems);
    }

    /**
     * Retorna os itens pendentes de preenchimento
     */
    public function getPendingItems(int $conversationId): array
    {
        $answeredIds = DB::table('conversation_triage')
            ->where('conversation_id', $conversationId)
            ->whereNotNull('answer')
            ->where('answer', '!=', '')
            ->pluck('checklist_item_id')
            ->toArray();

        return DB::table('triage_checklist_items')
            ->where('active', 1)
            ->where('is_required', 1)
            ->whereNotIn('id', $answeredIds)
            ->orderBy('display_order')
            ->get()
            ->map(function ($item) {
                $item->options = $item->options_json ? json_decode($item->options_json, true) : null;
                unset($item->options_json);
                return $item;
            })
            ->toArray();
    }

    /**
     * Calcula a urgência com base nas respostas da triagem
     */
    public function calculateUrgency(int $conversationId): string
    {
        $urgencyAnswer = DB::table('conversation_triage')
            ->join('triage_checklist_items', 'triage_checklist_items.id', '=', 'conversation_triage.checklist_item_id')
            ->where('conversation_triage.conversation_id', $conversationId)
            ->where('triage_checklist_items.code', 'urgency')
            ->value('answer');

        if (!$urgencyAnswer) {
            return 'normal';
        }

        // Mapeia urgência para prioridade
        $urgencyMap = [
            'Imediato (hoje/amanhã)' => 'urgent',
            'Esta semana' => 'high',
            'Próxima semana' => 'normal',
            'Sem urgência' => 'low',
        ];

        return $urgencyMap[$urgencyAnswer] ?? 'normal';
    }

    /**
     * Gera resumo da triagem para uso em propostas
     */
    public function generateTriageSummary(int $conversationId): array
    {
        $answers = $this->getTriageAnswers($conversationId);
        
        $summary = [
            'paciente' => [],
            'servico' => [],
            'localizacao' => [],
            'contato' => [],
        ];

        foreach ($answers as $answer) {
            if (empty($answer->answer)) {
                continue;
            }

            switch ($answer->code) {
                case 'patient_name':
                case 'patient_age':
                case 'patient_condition':
                case 'mobility_level':
                    $summary['paciente'][$answer->code] = $answer->answer;
                    break;

                case 'care_type':
                case 'schedule_days':
                case 'schedule_hours':
                case 'urgency':
                case 'special_requirements':
                case 'caregiver_preference_gender':
                case 'budget_range':
                    $summary['servico'][$answer->code] = $answer->answer;
                    break;

                case 'address_city':
                case 'address_neighborhood':
                    $summary['localizacao'][$answer->code] = $answer->answer;
                    break;

                case 'contact_relationship':
                    $summary['contato'][$answer->code] = $answer->answer;
                    break;
            }
        }

        return $summary;
    }
}
