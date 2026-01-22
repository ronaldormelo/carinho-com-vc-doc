<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\CaregiverRating;
use App\Services\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    public function __construct(
        private RatingService $ratingService
    ) {}

    /**
     * Lista avaliacoes de um cuidador.
     */
    public function index(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);

        $ratings = $caregiver->ratings()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $summary = $this->ratingService->getSummary($caregiver);

        return response()->json([
            'success' => true,
            'message' => 'Avaliacoes carregadas',
            'data' => $ratings->items(),
            'summary' => $summary,
            'pagination' => [
                'current_page' => $ratings->currentPage(),
                'per_page' => $ratings->perPage(),
                'total' => $ratings->total(),
                'last_page' => $ratings->lastPage(),
            ],
        ]);
    }

    /**
     * Registra nova avaliacao pos-servico.
     */
    public function store(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $validator = Validator::make($request->all(), [
            'service_id' => 'required|integer',
            'score' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        // Verifica se ja existe avaliacao para este servico
        $existing = $caregiver->ratings()
            ->where('service_id', $request->get('service_id'))
            ->first();

        if ($existing) {
            return $this->error('Ja existe uma avaliacao para este servico', 422);
        }

        $rating = CaregiverRating::create([
            'caregiver_id' => $caregiver->id,
            'service_id' => $request->get('service_id'),
            'score' => $request->get('score'),
            'comment' => $request->get('comment'),
            'created_at' => now(),
        ]);

        // Processa impacto da avaliacao
        $this->ratingService->processRatingImpact($rating);

        return $this->success($rating, 'Avaliacao registrada com sucesso', 201);
    }

    /**
     * Exibe avaliacao especifica.
     */
    public function show(int $caregiverId, int $ratingId): JsonResponse
    {
        $rating = CaregiverRating::where('caregiver_id', $caregiverId)
            ->where('id', $ratingId)
            ->first();

        if (!$rating) {
            return $this->error('Avaliacao nao encontrada', 404);
        }

        return $this->success($rating);
    }

    /**
     * Resumo de avaliacoes (metricas).
     */
    public function summary(int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $summary = $this->ratingService->getSummary($caregiver);

        return $this->success($summary, 'Resumo de avaliacoes');
    }

    /**
     * Lista cuidadores com melhor avaliacao.
     */
    public function topRated(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 10), 50);
        $city = $request->get('city');

        $topRated = $this->ratingService->getTopRated($limit, $city);

        return $this->success($topRated, 'Top cuidadores carregados');
    }

    /**
     * Lista cuidadores que precisam de atencao (notas baixas).
     */
    public function needsAttention(Request $request): JsonResponse
    {
        $threshold = (float) $request->get('threshold', config('cuidadores.avaliacoes.nota_alerta'));

        $caregivers = $this->ratingService->getNeedsAttention($threshold);

        return $this->success($caregivers, 'Cuidadores que precisam de atencao');
    }
}
