<?php

namespace App\Services;

use App\Models\Caregiver;
use App\Models\CaregiverRating;
use App\Jobs\SendCaregiverNotification;
use App\Jobs\SyncRatingWithCrm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RatingService
{
    /**
     * Processa impacto de uma nova avaliacao.
     */
    public function processRatingImpact(CaregiverRating $rating): void
    {
        $caregiver = $rating->caregiver;

        // Notifica cuidador sobre a avaliacao
        SendCaregiverNotification::dispatch($caregiver, 'rating_received', [
            'score' => $rating->score,
            'comment' => $rating->comment,
        ]);

        // Sincroniza com CRM
        SyncRatingWithCrm::dispatch($rating);

        // Verifica se precisa de atencao especial
        if ($rating->is_negative) {
            $this->handleNegativeRating($caregiver, $rating);
        }

        Log::info('Avaliacao processada', [
            'rating_id' => $rating->id,
            'caregiver_id' => $caregiver->id,
            'score' => $rating->score,
        ]);
    }

    /**
     * Trata avaliacoes negativas.
     */
    private function handleNegativeRating(Caregiver $caregiver, CaregiverRating $rating): void
    {
        // Conta avaliacoes negativas recentes
        $recentNegativeCount = $caregiver->ratings()
            ->recent(30)
            ->lowRated(2)
            ->count();

        // Se houver muitas avaliacoes negativas, marca para atencao
        if ($recentNegativeCount >= 3) {
            Log::warning('Cuidador com multiplas avaliacoes negativas', [
                'caregiver_id' => $caregiver->id,
                'negative_count' => $recentNegativeCount,
            ]);

            // Aqui poderia disparar alerta para admin
        }
    }

    /**
     * Retorna resumo de avaliacoes do cuidador.
     */
    public function getSummary(Caregiver $caregiver): array
    {
        $ratings = $caregiver->ratings;

        if ($ratings->isEmpty()) {
            return [
                'total_ratings' => 0,
                'average_rating' => null,
                'distribution' => [],
                'recent_trend' => null,
            ];
        }

        $distribution = $ratings->groupBy('score')
            ->map(fn ($group) => $group->count())
            ->toArray();

        // Preenche notas faltantes
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $distribution[$i] ?? 0;
        }
        ksort($distribution);

        // Calcula tendencia recente (ultimos 30 dias vs anterior)
        $recentAvg = $caregiver->ratings()->recent(30)->avg('score');
        $previousAvg = $caregiver->ratings()
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->avg('score');

        $trend = null;
        if ($recentAvg !== null && $previousAvg !== null) {
            $diff = $recentAvg - $previousAvg;
            $trend = match (true) {
                $diff > 0.2 => 'improving',
                $diff < -0.2 => 'declining',
                default => 'stable',
            };
        }

        return [
            'total_ratings' => $ratings->count(),
            'average_rating' => round($ratings->avg('score'), 2),
            'distribution' => $distribution,
            'positive_count' => $ratings->filter(fn ($r) => $r->is_positive)->count(),
            'negative_count' => $ratings->filter(fn ($r) => $r->is_negative)->count(),
            'recent_average' => $recentAvg ? round($recentAvg, 2) : null,
            'recent_trend' => $trend,
        ];
    }

    /**
     * Retorna cuidadores com melhor avaliacao.
     */
    public function getTopRated(int $limit = 10, ?string $city = null): array
    {
        $query = Caregiver::query()
            ->active()
            ->select('caregivers.*')
            ->selectRaw('AVG(caregiver_ratings.score) as avg_rating')
            ->selectRaw('COUNT(caregiver_ratings.id) as rating_count')
            ->join('caregiver_ratings', 'caregivers.id', '=', 'caregiver_ratings.caregiver_id')
            ->groupBy('caregivers.id')
            ->having('rating_count', '>=', 3) // Minimo de 3 avaliacoes
            ->orderByDesc('avg_rating')
            ->limit($limit);

        if ($city) {
            $query->where(function ($q) use ($city) {
                $q->where('city', $city)
                    ->orWhereHas('regions', fn ($q2) => $q2->where('city', $city));
            });
        }

        return $query->get()->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'city' => $c->city,
            'average_rating' => round($c->avg_rating, 2),
            'rating_count' => $c->rating_count,
            'experience_years' => $c->experience_years,
        ])->toArray();
    }

    /**
     * Retorna cuidadores que precisam de atencao (notas baixas).
     */
    public function getNeedsAttention(float $threshold = 3.0): array
    {
        return Caregiver::query()
            ->active()
            ->select('caregivers.*')
            ->selectRaw('AVG(caregiver_ratings.score) as avg_rating')
            ->selectRaw('COUNT(caregiver_ratings.id) as rating_count')
            ->join('caregiver_ratings', 'caregivers.id', '=', 'caregiver_ratings.caregiver_id')
            ->groupBy('caregivers.id')
            ->having('avg_rating', '<', $threshold)
            ->having('rating_count', '>=', 2) // Pelo menos 2 avaliacoes
            ->orderBy('avg_rating')
            ->limit(20)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'city' => $c->city,
                'average_rating' => round($c->avg_rating, 2),
                'rating_count' => $c->rating_count,
            ])
            ->toArray();
    }

    /**
     * Solicita avaliacao apos conclusao de servico.
     */
    public function requestRating(int $caregiverId, int $serviceId, string $clientPhone): array
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return ['success' => false, 'message' => 'Cuidador nao encontrado'];
        }

        // Verifica se ja existe avaliacao
        $existing = CaregiverRating::where('caregiver_id', $caregiverId)
            ->where('service_id', $serviceId)
            ->exists();

        if ($existing) {
            return ['success' => false, 'message' => 'Avaliacao ja existe para este servico'];
        }

        // Dispara solicitacao de avaliacao via sistema de atendimento
        // Isso sera processado pelo sistema de integracoes
        Log::info('Solicitacao de avaliacao disparada', [
            'caregiver_id' => $caregiverId,
            'service_id' => $serviceId,
            'client_phone' => $clientPhone,
        ]);

        return [
            'success' => true,
            'message' => 'Solicitacao de avaliacao enviada',
        ];
    }
}
