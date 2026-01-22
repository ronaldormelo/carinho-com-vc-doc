<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService
    ) {}

    /**
     * Busca avancada de cuidadores.
     *
     * Filtros disponiveis:
     * - city: cidade
     * - neighborhood: bairro
     * - care_types: array de tipos de cuidado (idoso, pcd, tea, pos_operatorio)
     * - skill_level: nivel minimo de habilidade
     * - min_experience: experiencia minima em anos
     * - min_rating: nota minima
     * - availability: array de dias da semana (0-6)
     * - status: status do cuidador (active, pending, etc.)
     */
    public function search(Request $request): JsonResponse
    {
        $filters = [
            'city' => $request->get('city'),
            'neighborhood' => $request->get('neighborhood'),
            'care_types' => $request->get('care_types', []),
            'skill_level' => $request->get('skill_level'),
            'min_experience' => $request->get('min_experience'),
            'min_rating' => $request->get('min_rating'),
            'availability' => $request->get('availability', []),
            'status' => $request->get('status', 'active'),
        ];

        $perPage = min(
            (int) $request->get('per_page', config('cuidadores.pagination.default_per_page')),
            config('cuidadores.pagination.max_per_page')
        );

        $sortBy = $request->get('sort_by', 'rating');
        $sortDir = $request->get('sort_dir', 'desc');

        $result = $this->searchService->search($filters, $perPage, $sortBy, $sortDir);

        return $this->paginated($result, 'Busca realizada com sucesso');
    }

    /**
     * Busca rapida por telefone ou nome.
     */
    public function quick(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return $this->error('Termo de busca muito curto', 422);
        }

        $results = $this->searchService->quickSearch($query, 10);

        return $this->success($results, 'Busca rapida concluida');
    }

    /**
     * Obtem opcoes de filtro disponiveis.
     */
    public function filters(): JsonResponse
    {
        $filters = $this->searchService->getAvailableFilters();

        return $this->success($filters, 'Filtros carregados');
    }

    /**
     * Busca cuidadores disponiveis para um horario especifico.
     */
    public function available(Request $request): JsonResponse
    {
        $dayOfWeek = (int) $request->get('day_of_week', now()->dayOfWeek);
        $time = $request->get('time', now()->format('H:i'));
        $city = $request->get('city');
        $careType = $request->get('care_type');

        $results = $this->searchService->findAvailableAt(
            $dayOfWeek,
            $time,
            $city,
            $careType
        );

        return $this->success($results, 'Cuidadores disponiveis carregados');
    }

    /**
     * Estatisticas do banco de cuidadores.
     */
    public function stats(): JsonResponse
    {
        $stats = $this->searchService->getStats();

        return $this->success($stats, 'Estatisticas carregadas');
    }
}
