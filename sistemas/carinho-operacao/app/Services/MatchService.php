<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\Assignment;
use App\Models\DomainAssignmentStatus;
use App\Integrations\Cuidadores\CuidadoresClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service para match de cliente com cuidador.
 */
class MatchService
{
    public function __construct(
        protected CuidadoresClient $cuidadoresClient,
        protected ScheduleService $scheduleService
    ) {}

    /**
     * Busca candidatos para uma solicitacao de servico.
     */
    public function findCandidates(ServiceRequest $serviceRequest, array $requirements = []): Collection
    {
        $config = config('operacao.matching');

        // Busca cuidadores disponiveis via sistema de cuidadores
        $availableCaregivers = $this->fetchAvailableCaregivers($serviceRequest, $requirements);

        if ($availableCaregivers->isEmpty()) {
            Log::warning('Nenhum cuidador disponivel encontrado', [
                'service_request_id' => $serviceRequest->id,
            ]);
            return collect();
        }

        // Calcula score para cada candidato
        $candidates = $availableCaregivers->map(function ($caregiver) use ($serviceRequest, $requirements, $config) {
            $scores = $this->calculateScores($caregiver, $serviceRequest, $requirements);

            return [
                'caregiver_id' => $caregiver['id'],
                'caregiver' => $caregiver,
                'scores' => $scores,
                'total_score' => $this->calculateTotalScore($scores, $config),
            ];
        });

        // Ordena por score e limita
        $candidates = $candidates
            ->sortByDesc('total_score')
            ->take($config['max_candidates']);

        Log::info('Candidatos encontrados para match', [
            'service_request_id' => $serviceRequest->id,
            'count' => $candidates->count(),
        ]);

        return $candidates->values();
    }

    /**
     * Realiza match automatico.
     */
    public function autoMatch(ServiceRequest $serviceRequest, array $requirements = []): ?Assignment
    {
        $config = config('operacao.matching');
        $candidates = $this->findCandidates($serviceRequest, $requirements);

        if ($candidates->isEmpty()) {
            return null;
        }

        $bestCandidate = $candidates->first();

        // Verifica se atinge score minimo para match automatico
        if ($bestCandidate['total_score'] < $config['min_auto_match_score']) {
            Log::info('Nenhum candidato atinge score minimo para auto-match', [
                'service_request_id' => $serviceRequest->id,
                'best_score' => $bestCandidate['total_score'],
                'min_required' => $config['min_auto_match_score'],
            ]);
            return null;
        }

        return $this->assignCaregiver($serviceRequest, $bestCandidate['caregiver_id']);
    }

    /**
     * Aloca cuidador manualmente.
     */
    public function assignCaregiver(ServiceRequest $serviceRequest, int $caregiverId): Assignment
    {
        return DB::transaction(function () use ($serviceRequest, $caregiverId) {
            // Cancela alocacoes anteriores ativas
            Assignment::where('service_request_id', $serviceRequest->id)
                ->whereIn('status_id', [DomainAssignmentStatus::ASSIGNED, DomainAssignmentStatus::CONFIRMED])
                ->update(['status_id' => DomainAssignmentStatus::REPLACED]);

            // Cria nova alocacao
            $assignment = Assignment::create([
                'service_request_id' => $serviceRequest->id,
                'caregiver_id' => $caregiverId,
                'status_id' => DomainAssignmentStatus::ASSIGNED,
                'assigned_at' => now(),
            ]);

            // Notifica sistema de cuidadores
            $this->cuidadoresClient->notifyAssignment($caregiverId, [
                'service_request_id' => $serviceRequest->id,
                'assignment_id' => $assignment->id,
                'client_id' => $serviceRequest->client_id,
                'start_date' => $serviceRequest->start_date?->toDateString(),
                'end_date' => $serviceRequest->end_date?->toDateString(),
            ]);

            Log::info('Cuidador alocado', [
                'assignment_id' => $assignment->id,
                'service_request_id' => $serviceRequest->id,
                'caregiver_id' => $caregiverId,
            ]);

            return $assignment;
        });
    }

    /**
     * Confirma alocacao pelo cuidador.
     */
    public function confirmAssignment(Assignment $assignment): Assignment
    {
        if (!$assignment->status_id === DomainAssignmentStatus::ASSIGNED) {
            throw new \InvalidArgumentException('Alocacao nao pode ser confirmada.');
        }

        $assignment->status_id = DomainAssignmentStatus::CONFIRMED;
        $assignment->save();

        Log::info('Alocacao confirmada', [
            'assignment_id' => $assignment->id,
        ]);

        return $assignment;
    }

    /**
     * Busca cuidadores disponiveis via integracao.
     */
    protected function fetchAvailableCaregivers(ServiceRequest $serviceRequest, array $requirements): Collection
    {
        $cacheKey = "available_caregivers:{$serviceRequest->id}";

        return Cache::remember($cacheKey, 60, function () use ($serviceRequest, $requirements) {
            $filters = [
                'service_type' => $serviceRequest->service_type_id,
                'start_date' => $serviceRequest->start_date?->toDateString(),
                'end_date' => $serviceRequest->end_date?->toDateString(),
                'urgency' => $serviceRequest->urgency_id,
                'skills' => $requirements['skills'] ?? [],
                'region' => $requirements['region'] ?? null,
                'max_radius_km' => config('operacao.matching.max_radius_km'),
            ];

            $response = $this->cuidadoresClient->findAvailable($filters);

            if (!$response['ok']) {
                Log::error('Erro ao buscar cuidadores disponiveis', [
                    'error' => $response['error'] ?? 'Unknown error',
                ]);
                return collect();
            }

            return collect($response['body']['caregivers'] ?? []);
        });
    }

    /**
     * Calcula scores individuais para um candidato.
     */
    protected function calculateScores(array $caregiver, ServiceRequest $serviceRequest, array $requirements): array
    {
        return [
            'skill' => $this->calculateSkillScore($caregiver, $requirements),
            'availability' => $this->calculateAvailabilityScore($caregiver, $serviceRequest),
            'region' => $this->calculateRegionScore($caregiver, $requirements),
            'rating' => $this->calculateRatingScore($caregiver),
        ];
    }

    /**
     * Calcula score de habilidades.
     */
    protected function calculateSkillScore(array $caregiver, array $requirements): float
    {
        $requiredSkills = $requirements['skills'] ?? [];
        if (empty($requiredSkills)) {
            return 100;
        }

        $caregiverSkills = $caregiver['skills'] ?? [];
        $matchedSkills = array_intersect($requiredSkills, $caregiverSkills);

        return count($requiredSkills) > 0
            ? (count($matchedSkills) / count($requiredSkills)) * 100
            : 100;
    }

    /**
     * Calcula score de disponibilidade.
     */
    protected function calculateAvailabilityScore(array $caregiver, ServiceRequest $serviceRequest): float
    {
        $availability = $caregiver['availability'] ?? [];

        // Verifica se esta disponivel nas datas solicitadas
        $startDate = $serviceRequest->start_date;
        $endDate = $serviceRequest->end_date;

        if (!$startDate) {
            return 100; // Sem data definida, considera disponivel
        }

        // Simplificacao: verifica se tem disponibilidade geral
        $hasAvailability = !empty($availability);

        return $hasAvailability ? 100 : 0;
    }

    /**
     * Calcula score de regiao/proximidade.
     */
    protected function calculateRegionScore(array $caregiver, array $requirements): float
    {
        $requestedRegion = $requirements['region'] ?? null;
        if (!$requestedRegion) {
            return 100;
        }

        $caregiverRegions = $caregiver['regions'] ?? [];
        $maxRadius = config('operacao.matching.max_radius_km');

        // Verifica se atende a regiao
        if (in_array($requestedRegion, $caregiverRegions)) {
            return 100;
        }

        // Calcula score baseado em distancia (se disponivel)
        $distance = $caregiver['distance_km'] ?? null;
        if ($distance !== null && $maxRadius > 0) {
            return max(0, (1 - ($distance / $maxRadius)) * 100);
        }

        return 50; // Score medio se nao tem info de regiao
    }

    /**
     * Calcula score baseado em avaliacao.
     */
    protected function calculateRatingScore(array $caregiver): float
    {
        $rating = $caregiver['average_rating'] ?? 0;
        $maxRating = 5;

        return ($rating / $maxRating) * 100;
    }

    /**
     * Calcula score total ponderado.
     */
    protected function calculateTotalScore(array $scores, array $config): float
    {
        return ($scores['skill'] * $config['skill_weight']) +
               ($scores['availability'] * $config['availability_weight']) +
               ($scores['region'] * $config['region_weight']) +
               ($scores['rating'] * $config['rating_weight']);
    }

    /**
     * Verifica compatibilidade entre cliente e cuidador.
     */
    public function checkCompatibility(int $clientId, int $caregiverId): array
    {
        // Busca historico de atendimentos anteriores
        $previousAssignments = Assignment::forCaregiver($caregiverId)
            ->whereHas('serviceRequest', fn($q) => $q->where('client_id', $clientId))
            ->with('serviceRequest')
            ->get();

        $totalServices = $previousAssignments->count();

        if ($totalServices === 0) {
            return [
                'has_history' => false,
                'compatibility_score' => 75, // Score neutro para novos
                'previous_services' => 0,
                'message' => 'Primeira vez que este cuidador atende este cliente.',
            ];
        }

        // Calcula baseado em historico
        $completedServices = $previousAssignments->filter(function ($assignment) {
            return $assignment->status_id === DomainAssignmentStatus::CONFIRMED ||
                   $assignment->serviceRequest->isCompleted();
        })->count();

        $completionRate = ($completedServices / $totalServices) * 100;

        return [
            'has_history' => true,
            'compatibility_score' => min(100, $completionRate + 10),
            'previous_services' => $totalServices,
            'completed_services' => $completedServices,
            'message' => "Cuidador ja atendeu este cliente {$totalServices} vez(es).",
        ];
    }
}
