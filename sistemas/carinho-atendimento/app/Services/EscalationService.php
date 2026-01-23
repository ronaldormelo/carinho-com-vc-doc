<?php

namespace App\Services;

use App\Jobs\NotifyOperacaoJob;
use App\Repositories\AtendimentoRepository;
use App\Support\DomainLookup;
use Illuminate\Support\Facades\DB;

/**
 * Servico de escalonamento de atendimentos por niveis (N1, N2, N3).
 *
 * Regras de escalonamento:
 * - N1 (Atendente): Primeiro contato, triagem basica, duvidas simples
 * - N2 (Supervisor): Reclamacoes, casos complexos, exceções operacionais
 * - N3 (Gestao): Emergencias, crises, decisoes estrategicas
 */
class EscalationService
{
    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup,
        private ConversationHistoryService $historyService
    ) {
    }

    /**
     * Escalona uma conversa para o proximo nivel de suporte.
     */
    public function escalate(int $conversationId, ?int $agentId = null, ?string $reason = null): void
    {
        $conversation = $this->repository->findConversationById($conversationId);

        if (!$conversation) {
            throw new \InvalidArgumentException('Conversa nao encontrada.');
        }

        $currentLevel = DB::table('domain_support_level')
            ->where('id', $conversation->support_level_id)
            ->first();

        if (!$currentLevel) {
            return;
        }

        $nextLevelCode = $this->getNextLevelCode($currentLevel->code);

        if (!$nextLevelCode) {
            // Ja esta no nivel maximo (N3)
            return;
        }

        $nextLevelId = $this->domainLookup->supportLevelId($nextLevelCode);

        $this->repository->updateConversation($conversationId, [
            'support_level_id' => $nextLevelId,
            'updated_at' => now()->toDateTimeString(),
        ]);

        // Registra no historico
        $this->historyService->record(
            $conversationId,
            'escalation',
            $agentId,
            $currentLevel->code,
            $nextLevelCode,
            $reason
        );

        // Notifica operacao em escalonamentos N3
        if ($nextLevelCode === 'n3') {
            $this->notifyManagement($conversationId, $reason);
        }
    }

    /**
     * Rebaixa o nivel de suporte (quando resolvido parcialmente).
     */
    public function deescalate(int $conversationId, ?int $agentId = null, ?string $reason = null): void
    {
        $conversation = $this->repository->findConversationById($conversationId);

        if (!$conversation) {
            throw new \InvalidArgumentException('Conversa nao encontrada.');
        }

        $currentLevel = DB::table('domain_support_level')
            ->where('id', $conversation->support_level_id)
            ->first();

        if (!$currentLevel) {
            return;
        }

        $previousLevelCode = $this->getPreviousLevelCode($currentLevel->code);

        if (!$previousLevelCode) {
            // Ja esta no nivel minimo (N1)
            return;
        }

        $previousLevelId = $this->domainLookup->supportLevelId($previousLevelCode);

        $this->repository->updateConversation($conversationId, [
            'support_level_id' => $previousLevelId,
            'updated_at' => now()->toDateTimeString(),
        ]);

        $this->historyService->record(
            $conversationId,
            'escalation',
            $agentId,
            $currentLevel->code,
            $previousLevelCode,
            $reason ?? 'Rebaixamento de nivel'
        );
    }

    /**
     * Verifica conversas que precisam de escalonamento automatico por tempo.
     */
    public function checkAutoEscalation(): array
    {
        $escalated = [];

        $levels = DB::table('domain_support_level')
            ->whereIn('code', ['n1', 'n2'])
            ->get();

        foreach ($levels as $level) {
            $conversations = DB::table('conversations')
                ->join('sla_metrics', 'sla_metrics.conversation_id', '=', 'conversations.id')
                ->where('conversations.support_level_id', $level->id)
                ->whereNull('conversations.closed_at')
                ->whereNull('sla_metrics.first_response_at')
                ->whereRaw(
                    'TIMESTAMPDIFF(MINUTE, conversations.started_at, NOW()) > ?',
                    [$level->escalation_minutes]
                )
                ->select('conversations.id')
                ->get();

            foreach ($conversations as $conv) {
                $this->escalate(
                    $conv->id,
                    null,
                    "Escalonamento automatico: sem resposta em {$level->escalation_minutes} minutos"
                );
                $escalated[] = $conv->id;
            }
        }

        return $escalated;
    }

    /**
     * Retorna estatisticas de escalonamento.
     */
    public function getEscalationStats(string $period = 'today'): array
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        $stats = DB::table('conversation_history')
            ->join('domain_action_type', 'domain_action_type.id', '=', 'conversation_history.action_type_id')
            ->where('domain_action_type.code', 'escalation')
            ->where('conversation_history.created_at', '>=', $startDate)
            ->selectRaw('new_value as level, COUNT(*) as total')
            ->groupBy('new_value')
            ->get()
            ->pluck('total', 'level')
            ->toArray();

        return [
            'period' => $period,
            'to_n2' => $stats['n2'] ?? 0,
            'to_n3' => $stats['n3'] ?? 0,
            'total' => array_sum($stats),
        ];
    }

    private function getNextLevelCode(string $currentCode): ?string
    {
        return match ($currentCode) {
            'n1' => 'n2',
            'n2' => 'n3',
            default => null,
        };
    }

    private function getPreviousLevelCode(string $currentCode): ?string
    {
        return match ($currentCode) {
            'n3' => 'n2',
            'n2' => 'n1',
            default => null,
        };
    }

    private function notifyManagement(int $conversationId, ?string $reason): void
    {
        $conversation = $this->repository->findConversationById($conversationId);
        $contact = $conversation ? $this->repository->findContactById($conversation->contact_id) : null;

        NotifyOperacaoJob::dispatch([
            'type' => 'escalation_n3',
            'conversation_id' => $conversationId,
            'reason' => $reason,
            'contact' => $contact ? [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->phone,
            ] : null,
        ]);
    }
}
