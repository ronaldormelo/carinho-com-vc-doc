<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SyncService;
use App\Jobs\SyncSystems;
use App\Models\SyncJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para operacoes de sincronizacao entre sistemas.
 */
class SyncController extends Controller
{
    public function __construct(
        private SyncService $syncService
    ) {}

    /**
     * Lista jobs de sincronizacao.
     *
     * GET /api/sync/jobs
     */
    public function index(Request $request): JsonResponse
    {
        $query = SyncJob::with('status');

        if ($request->has('type')) {
            $query->where('job_type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status_id', $request->status);
        }

        $jobs = $query->orderByDesc('id')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $jobs->items(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ],
        ]);
    }

    /**
     * Inicia sincronizacao.
     *
     * POST /api/sync/start
     */
    public function start(Request $request): JsonResponse
    {
        $type = $request->get('type', 'full');

        $validTypes = [
            'full',
            'crm_operacao',
            'operacao_financeiro',
            'crm_financeiro',
            'cuidadores_crm',
        ];

        if (!in_array($type, $validTypes)) {
            return response()->json([
                'error' => 'Invalid sync type',
                'valid_types' => $validTypes,
            ], 422);
        }

        // Verifica se ja tem sync rodando do mesmo tipo
        if (SyncJob::hasRunningJob($type === 'full' ? 'sync.full' : "sync.{$type}")) {
            return response()->json([
                'error' => 'Sync job already running',
                'type' => $type,
            ], 409);
        }

        // Despacha job de sincronizacao
        SyncSystems::dispatch($type);

        return response()->json([
            'message' => 'Sync job started',
            'type' => $type,
        ], 202);
    }

    /**
     * Exibe detalhes de um job de sincronizacao.
     *
     * GET /api/sync/jobs/{id}
     */
    public function show(int $id): JsonResponse
    {
        $job = SyncJob::with('status')->find($id);

        if (!$job) {
            return response()->json([
                'error' => 'Sync job not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $job->id,
                'job_type' => $job->job_type,
                'status' => $job->status->code,
                'started_at' => $job->started_at?->toIso8601String(),
                'finished_at' => $job->finished_at?->toIso8601String(),
                'duration_seconds' => $job->getDurationInSeconds(),
            ],
        ]);
    }

    /**
     * Estatisticas de sincronizacao.
     *
     * GET /api/sync/stats
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => $this->syncService->getStats(),
        ]);
    }
}
