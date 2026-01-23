<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientReview;
use App\Models\ClientEvent;
use App\Models\Domain\DomainEventType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

/**
 * Serviço de Revisões Periódicas de Clientes
 * 
 * Prática tradicional de gestão de relacionamento para:
 * - Avaliar satisfação e qualidade do serviço
 * - Identificar riscos de churn
 * - Identificar clientes promotores
 * - Documentar ações de melhoria
 */
class ClientReviewService
{
    /**
     * Cria uma nova revisão de cliente
     */
    public function createReview(Client $client, array $data): ClientReview
    {
        return DB::transaction(function () use ($client, $data) {
            // Cria a revisão
            $review = $client->reviews()->create([
                'reviewed_by' => $data['reviewed_by'] ?? auth()->id(),
                'review_date' => $data['review_date'] ?? now(),
                'satisfaction_score' => $data['satisfaction_score'] ?? null,
                'service_quality_score' => $data['service_quality_score'] ?? null,
                'contract_renewal_intent' => $data['contract_renewal_intent'] ?? null,
                'observations' => $data['observations'] ?? null,
                'action_items' => $data['action_items'] ?? null,
                'next_review_date' => $data['next_review_date'] ?? null,
            ]);

            // Atualiza data da última revisão no cliente
            $client->last_review_date = $review->review_date;
            
            // Agenda próxima revisão se não foi informada
            if (empty($data['next_review_date']) && $client->reviewFrequency) {
                $client->scheduleNextReview($review->review_date);
            } else {
                $client->next_review_date = $data['next_review_date'] ?? null;
            }
            
            $client->save();

            // Registra evento na timeline
            ClientEvent::logReviewCompleted($client, $review);

            // Log de auditoria
            Log::channel('audit')->info('Revisão de cliente realizada', [
                'client_id' => $client->id,
                'review_id' => $review->id,
                'satisfaction' => $review->satisfaction_score,
                'renewal_intent' => $review->contract_renewal_intent,
            ]);

            return $review;
        });
    }

    /**
     * Atualiza uma revisão existente
     */
    public function updateReview(ClientReview $review, array $data): ClientReview
    {
        return DB::transaction(function () use ($review, $data) {
            $review->update($data);

            Log::channel('audit')->info('Revisão de cliente atualizada', [
                'client_id' => $review->client_id,
                'review_id' => $review->id,
            ]);

            return $review->fresh();
        });
    }

    /**
     * Obtém clientes que precisam de revisão
     */
    public function getClientsNeedingReview(): Collection
    {
        return Client::with(['classification', 'reviewFrequency', 'reviews' => function ($q) {
            $q->latest('review_date')->limit(1);
        }])
        ->needsReview()
        ->withActiveContracts()
        ->orderBy('next_review_date', 'asc')
        ->get();
    }

    /**
     * Obtém clientes com revisão próxima
     */
    public function getClientsWithUpcomingReview(int $days = 7): Collection
    {
        return Client::with(['classification', 'reviewFrequency'])
            ->reviewDueSoon($days)
            ->withActiveContracts()
            ->orderBy('next_review_date', 'asc')
            ->get();
    }

    /**
     * Obtém clientes em risco de churn (baseado em revisões)
     */
    public function getChurnRiskClients(): Collection
    {
        // Clientes com última revisão indicando risco
        return Client::with(['reviews' => function ($q) {
            $q->latest('review_date')->limit(1);
        }])
        ->withActiveContracts()
        ->whereHas('reviews', function ($q) {
            $q->where(function ($query) {
                $query->where('satisfaction_score', '<=', 2)
                      ->orWhere('contract_renewal_intent', false);
            });
        })
        ->get()
        ->filter(fn($client) => $client->reviews->first()?->isChurnRisk());
    }

    /**
     * Obtém clientes promotores (potencial indicação)
     */
    public function getPromoterClients(): Collection
    {
        return Client::with(['reviews' => function ($q) {
            $q->latest('review_date')->limit(1);
        }])
        ->withActiveContracts()
        ->whereHas('reviews', function ($q) {
            $q->where('satisfaction_score', '>=', 4)
              ->where('contract_renewal_intent', true);
        })
        ->get()
        ->filter(fn($client) => $client->reviews->first()?->isPromoter());
    }

    /**
     * Obtém estatísticas de revisões
     */
    public function getStatistics($startDate = null, $endDate = null): array
    {
        $query = ClientReview::query();

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        $reviews = $query->get();
        $total = $reviews->count();

        return [
            'total_reviews' => $total,
            'avg_satisfaction' => round($reviews->avg('satisfaction_score') ?? 0, 1),
            'avg_quality' => round($reviews->avg('service_quality_score') ?? 0, 1),
            'with_renewal_intent' => $reviews->where('contract_renewal_intent', true)->count(),
            'without_renewal_intent' => $reviews->where('contract_renewal_intent', false)->count(),
            'churn_risk_count' => $reviews->filter(fn($r) => $r->isChurnRisk())->count(),
            'promoter_count' => $reviews->filter(fn($r) => $r->isPromoter())->count(),
            'satisfaction_distribution' => [
                '5' => $reviews->where('satisfaction_score', 5)->count(),
                '4' => $reviews->where('satisfaction_score', 4)->count(),
                '3' => $reviews->where('satisfaction_score', 3)->count(),
                '2' => $reviews->where('satisfaction_score', 2)->count(),
                '1' => $reviews->where('satisfaction_score', 1)->count(),
            ],
            'renewal_rate' => $total > 0 
                ? round(($reviews->where('contract_renewal_intent', true)->count() / $total) * 100, 1)
                : 0,
        ];
    }

    /**
     * Obtém histórico de revisões de um cliente
     */
    public function getClientReviewHistory(Client $client): Collection
    {
        return $client->reviews()
            ->with('reviewer')
            ->orderBy('review_date', 'desc')
            ->get();
    }

    /**
     * Calcular NPS (Net Promoter Score) baseado nas revisões
     */
    public function calculateNPS($startDate = null, $endDate = null): array
    {
        $query = ClientReview::whereNotNull('satisfaction_score');

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        $reviews = $query->get();
        $total = $reviews->count();

        if ($total === 0) {
            return [
                'nps' => 0,
                'promoters' => 0,
                'passives' => 0,
                'detractors' => 0,
                'total_responses' => 0,
            ];
        }

        // NPS: Promoters (4-5) - Detractors (1-2), escala 1-5 adaptada
        $promoters = $reviews->whereIn('satisfaction_score', [4, 5])->count();
        $passives = $reviews->where('satisfaction_score', 3)->count();
        $detractors = $reviews->whereIn('satisfaction_score', [1, 2])->count();

        $nps = round((($promoters / $total) - ($detractors / $total)) * 100);

        return [
            'nps' => $nps,
            'promoters' => $promoters,
            'passives' => $passives,
            'detractors' => $detractors,
            'total_responses' => $total,
            'promoter_percentage' => round(($promoters / $total) * 100, 1),
            'detractor_percentage' => round(($detractors / $total) * 100, 1),
        ];
    }
}
