<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessageJob;
use App\Repositories\AtendimentoRepository;
use App\Support\DomainLookup;
use Illuminate\Support\Facades\DB;

/**
 * Servico de pesquisa de satisfacao do cliente.
 *
 * Gerencia envio e coleta de feedback apos atendimentos,
 * com calculo de NPS (Net Promoter Score) e metricas de satisfacao.
 *
 * Escala de notas:
 * - 1-2: Detratores (Insatisfeitos)
 * - 3: Neutros
 * - 4-5: Promotores (Satisfeitos)
 */
class SatisfactionService
{
    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup
    ) {
    }

    /**
     * Envia pesquisa de satisfacao para uma conversa encerrada.
     */
    public function sendSurvey(int $conversationId): ?int
    {
        $conversation = $this->repository->findConversationById($conversationId);

        if (!$conversation) {
            return null;
        }

        // Verifica se ja foi enviada pesquisa
        $existing = DB::table('satisfaction_surveys')
            ->where('conversation_id', $conversationId)
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $contact = $this->repository->findContactById($conversation->contact_id);

        if (!$contact) {
            return null;
        }

        // Cria registro da pesquisa
        $surveyId = DB::table('satisfaction_surveys')->insertGetId([
            'conversation_id' => $conversationId,
            'score' => null,
            'feedback' => null,
            'sent_at' => now()->toDateTimeString(),
            'responded_at' => null,
        ]);

        // Envia mensagem de pesquisa via WhatsApp
        $template = $this->repository->findAutoRuleTemplate('feedback_request');

        if ($template) {
            $messageId = $this->repository->createMessage([
                'conversation_id' => $conversationId,
                'direction_id' => $this->domainLookup->messageDirectionId('outbound'),
                'body' => $template->body,
                'media_url' => null,
                'sent_at' => null,
                'status_id' => $this->domainLookup->messageStatusId('queued'),
            ]);

            SendWhatsAppMessageJob::dispatch(
                $conversationId,
                $messageId,
                $contact->phone,
                $template->body,
                null
            );
        }

        return $surveyId;
    }

    /**
     * Registra resposta da pesquisa de satisfacao.
     */
    public function recordResponse(int $conversationId, int $score, ?string $feedback = null): bool
    {
        if ($score < 1 || $score > 5) {
            return false;
        }

        $survey = DB::table('satisfaction_surveys')
            ->where('conversation_id', $conversationId)
            ->first();

        if (!$survey) {
            // Cria pesquisa se nao existir
            DB::table('satisfaction_surveys')->insert([
                'conversation_id' => $conversationId,
                'score' => $score,
                'feedback' => $feedback,
                'sent_at' => now()->toDateTimeString(),
                'responded_at' => now()->toDateTimeString(),
            ]);
            return true;
        }

        DB::table('satisfaction_surveys')
            ->where('id', $survey->id)
            ->update([
                'score' => $score,
                'feedback' => $feedback,
                'responded_at' => now()->toDateTimeString(),
            ]);

        return true;
    }

    /**
     * Calcula o NPS (Net Promoter Score).
     *
     * NPS = % Promotores - % Detratores
     * Escala: -100 a +100
     */
    public function calculateNps(string $period = 'month'): array
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $responses = DB::table('satisfaction_surveys')
            ->where('sent_at', '>=', $startDate)
            ->whereNotNull('score')
            ->select('score')
            ->get();

        $total = $responses->count();

        if ($total === 0) {
            return [
                'nps' => 0,
                'total_responses' => 0,
                'promoters' => 0,
                'neutrals' => 0,
                'detractors' => 0,
                'promoters_pct' => 0,
                'neutrals_pct' => 0,
                'detractors_pct' => 0,
                'period' => $period,
            ];
        }

        $promoters = $responses->where('score', '>=', 4)->count();
        $neutrals = $responses->where('score', '=', 3)->count();
        $detractors = $responses->where('score', '<=', 2)->count();

        $promotersPct = ($promoters / $total) * 100;
        $detractorsPct = ($detractors / $total) * 100;

        $nps = round($promotersPct - $detractorsPct);

        return [
            'nps' => $nps,
            'total_responses' => $total,
            'promoters' => $promoters,
            'neutrals' => $neutrals,
            'detractors' => $detractors,
            'promoters_pct' => round($promotersPct, 1),
            'neutrals_pct' => round(($neutrals / $total) * 100, 1),
            'detractors_pct' => round($detractorsPct, 1),
            'period' => $period,
        ];
    }

    /**
     * Obtem metricas gerais de satisfacao.
     */
    public function getMetrics(string $period = 'month'): array
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $surveys = DB::table('satisfaction_surveys')
            ->where('sent_at', '>=', $startDate)
            ->get();

        $sent = $surveys->count();
        $responded = $surveys->whereNotNull('responded_at')->count();
        $avgScore = $surveys->whereNotNull('score')->avg('score');

        $responseRate = $sent > 0 ? round(($responded / $sent) * 100, 1) : 0;

        $npsData = $this->calculateNps($period);

        return [
            'period' => $period,
            'surveys_sent' => $sent,
            'surveys_responded' => $responded,
            'response_rate' => $responseRate,
            'avg_score' => $avgScore ? round($avgScore, 2) : 0,
            'nps' => $npsData['nps'],
            'distribution' => [
                'promoters' => $npsData['promoters'],
                'neutrals' => $npsData['neutrals'],
                'detractors' => $npsData['detractors'],
            ],
        ];
    }

    /**
     * Obtem feedbacks recentes com notas baixas para acompanhamento.
     */
    public function getLowScoreFeedbacks(int $limit = 10): array
    {
        return DB::table('satisfaction_surveys')
            ->join('conversations', 'conversations.id', '=', 'satisfaction_surveys.conversation_id')
            ->join('contacts', 'contacts.id', '=', 'conversations.contact_id')
            ->where('satisfaction_surveys.score', '<=', 2)
            ->whereNotNull('satisfaction_surveys.responded_at')
            ->orderByDesc('satisfaction_surveys.responded_at')
            ->limit($limit)
            ->select([
                'satisfaction_surveys.id',
                'satisfaction_surveys.conversation_id',
                'satisfaction_surveys.score',
                'satisfaction_surveys.feedback',
                'satisfaction_surveys.responded_at',
                'contacts.name as contact_name',
                'contacts.phone as contact_phone',
            ])
            ->get()
            ->toArray();
    }

    /**
     * Classifica a nota em categoria.
     */
    public function classifyScore(int $score): string
    {
        return match (true) {
            $score >= 4 => 'promoter',
            $score === 3 => 'neutral',
            default => 'detractor',
        };
    }
}
