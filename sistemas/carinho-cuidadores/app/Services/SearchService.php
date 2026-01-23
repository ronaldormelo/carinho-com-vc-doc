<?php

namespace App\Services;

use App\Models\Caregiver;
use App\Models\CaregiverAvailability;
use App\Models\CaregiverRegion;
use App\Models\DomainCaregiverStatus;
use App\Models\DomainCareType;
use App\Models\DomainSkillLevel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Busca avancada de cuidadores.
     */
    public function search(array $filters, int $perPage, string $sortBy, string $sortDir): LengthAwarePaginator
    {
        $query = Caregiver::query()
            ->with(['status', 'skills.careType', 'skills.level', 'regions', 'availability']);

        // Filtro por status
        if (!empty($filters['status'])) {
            $query->whereHas('status', fn ($q) => $q->where('code', $filters['status']));
        }

        // Filtro por cidade
        if (!empty($filters['city'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('city', $filters['city'])
                    ->orWhereHas('regions', fn ($q2) => $q2->where('city', $filters['city']));
            });
        }

        // Filtro por bairro
        if (!empty($filters['neighborhood'])) {
            $query->whereHas('regions', fn ($q) => $q->where('neighborhood', $filters['neighborhood']));
        }

        // Filtro por tipos de cuidado
        if (!empty($filters['care_types']) && is_array($filters['care_types'])) {
            $query->whereHas('skills', function ($q) use ($filters) {
                $q->whereHas('careType', fn ($q2) => $q2->whereIn('code', $filters['care_types']));
            });
        }

        // Filtro por nivel de habilidade minimo
        if (!empty($filters['skill_level'])) {
            $levelOrder = ['basico' => 1, 'intermediario' => 2, 'avancado' => 3];
            $minLevel = $levelOrder[$filters['skill_level']] ?? 1;

            $query->whereHas('skills', function ($q) use ($minLevel) {
                $q->whereHas('level', fn ($q2) => $q2->where('id', '>=', $minLevel));
            });
        }

        // Filtro por experiencia minima
        if (!empty($filters['min_experience'])) {
            $query->where('experience_years', '>=', (int) $filters['min_experience']);
        }

        // Filtro por nota minima
        if (!empty($filters['min_rating'])) {
            $minRating = (float) $filters['min_rating'];
            $query->whereHas('ratings', function ($q) use ($minRating) {
                $q->havingRaw('AVG(score) >= ?', [$minRating]);
            }, '>=', 1);
        }

        // Filtro por disponibilidade
        if (!empty($filters['availability']) && is_array($filters['availability'])) {
            $query->whereHas('availability', fn ($q) => $q->whereIn('day_of_week', $filters['availability']));
        }

        // Ordenacao
        $sortBy = $this->resolveSortColumn($sortBy);
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Busca rapida por telefone ou nome.
     */
    public function quickSearch(string $term, int $limit = 10): array
    {
        $normalizedPhone = preg_replace('/\D+/', '', $term);

        $query = Caregiver::query()
            ->with(['status'])
            ->where(function ($q) use ($term, $normalizedPhone) {
                $q->where('name', 'LIKE', "%{$term}%");

                if (strlen($normalizedPhone) >= 4) {
                    $q->orWhere('phone', 'LIKE', "%{$normalizedPhone}%");
                }

                if (filter_var($term, FILTER_VALIDATE_EMAIL)) {
                    $q->orWhere('email', 'LIKE', "%{$term}%");
                }
            })
            ->limit($limit);

        return $query->get()->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'phone' => $c->phone,
            'city' => $c->city,
            'status' => $c->status?->code,
        ])->toArray();
    }

    /**
     * Busca cuidadores disponiveis em um horario especifico.
     */
    public function findAvailableAt(int $dayOfWeek, string $time, ?string $city = null, ?string $careType = null): array
    {
        $query = Caregiver::query()
            ->active()
            ->with(['skills.careType', 'regions'])
            ->whereHas('availability', function ($q) use ($dayOfWeek, $time) {
                $q->where('day_of_week', $dayOfWeek)
                    ->where('start_time', '<=', $time)
                    ->where('end_time', '>=', $time);
            });

        if ($city) {
            $query->where(function ($q) use ($city) {
                $q->where('city', $city)
                    ->orWhereHas('regions', fn ($q2) => $q2->where('city', $city));
            });
        }

        if ($careType) {
            $query->whereHas('skills', function ($q) use ($careType) {
                $q->whereHas('careType', fn ($q2) => $q2->where('code', $careType));
            });
        }

        return $query->get()->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'phone' => $c->phone,
            'city' => $c->city,
            'experience_years' => $c->experience_years,
            'average_rating' => $c->average_rating,
            'skills' => $c->skills->map(fn ($s) => $s->careType?->code)->toArray(),
        ])->toArray();
    }

    /**
     * Retorna filtros disponiveis.
     */
    public function getAvailableFilters(): array
    {
        $cacheKey = config('cuidadores.cache.prefix') . '_filters';
        $cacheTtl = config('cuidadores.cache.ttl_seconds', 300);

        if (config('cuidadores.cache.enabled')) {
            return Cache::remember($cacheKey, $cacheTtl, fn () => $this->buildFilters());
        }

        return $this->buildFilters();
    }

    /**
     * Constroi array de filtros.
     */
    private function buildFilters(): array
    {
        return [
            'statuses' => DomainCaregiverStatus::all()->map(fn ($s) => [
                'code' => $s->code,
                'label' => $s->label,
            ])->toArray(),

            'care_types' => DomainCareType::all()->map(fn ($t) => [
                'code' => $t->code,
                'label' => $t->label,
            ])->toArray(),

            'skill_levels' => DomainSkillLevel::all()->map(fn ($l) => [
                'code' => $l->code,
                'label' => $l->label,
            ])->toArray(),

            'cities' => CaregiverRegion::select('city')
                ->distinct()
                ->orderBy('city')
                ->pluck('city')
                ->toArray(),

            'days_of_week' => CaregiverAvailability::DAYS,
        ];
    }

    /**
     * Retorna estatisticas do banco de cuidadores.
     */
    public function getStats(): array
    {
        $cacheKey = config('cuidadores.cache.prefix') . '_stats';
        $cacheTtl = config('cuidadores.cache.ttl_seconds', 300);

        if (config('cuidadores.cache.enabled')) {
            return Cache::remember($cacheKey, $cacheTtl, fn () => $this->buildStats());
        }

        return $this->buildStats();
    }

    /**
     * Constroi estatisticas.
     */
    private function buildStats(): array
    {
        return [
            'total' => Caregiver::count(),
            'by_status' => Caregiver::select('status_id', DB::raw('COUNT(*) as count'))
                ->with('status')
                ->groupBy('status_id')
                ->get()
                ->mapWithKeys(fn ($row) => [$row->status?->code => $row->count])
                ->toArray(),

            'by_city' => Caregiver::select('city', DB::raw('COUNT(*) as count'))
                ->groupBy('city')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'city')
                ->toArray(),

            'by_care_type' => DB::table('caregiver_skills')
                ->join('domain_care_type', 'caregiver_skills.care_type_id', '=', 'domain_care_type.id')
                ->select('domain_care_type.code', DB::raw('COUNT(DISTINCT caregiver_id) as count'))
                ->groupBy('domain_care_type.code')
                ->pluck('count', 'code')
                ->toArray(),

            'average_experience' => round(Caregiver::avg('experience_years') ?? 0, 1),

            'average_rating' => round(DB::table('caregiver_ratings')->avg('score') ?? 0, 2),

            'new_last_30_days' => Caregiver::where('created_at', '>=', now()->subDays(30))->count(),

            'activated_last_30_days' => Caregiver::whereHas('statusHistory', function ($q) {
                $q->where('changed_at', '>=', now()->subDays(30))
                    ->whereHas('status', fn ($q2) => $q2->where('code', 'active'));
            })->count(),
        ];
    }

    /**
     * Resolve coluna de ordenacao.
     */
    private function resolveSortColumn(string $sortBy): string
    {
        return match ($sortBy) {
            'name' => 'name',
            'city' => 'city',
            'experience' => 'experience_years',
            'created_at' => 'created_at',
            default => 'created_at',
        };
    }
}
