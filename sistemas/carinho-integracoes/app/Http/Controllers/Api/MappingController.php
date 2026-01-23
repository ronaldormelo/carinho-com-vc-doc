<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller para gerenciamento de mapeamentos de eventos.
 */
class MappingController extends Controller
{
    /**
     * Lista mapeamentos.
     *
     * GET /api/mappings
     */
    public function index(Request $request): JsonResponse
    {
        $query = EventMapping::query();

        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->has('target_system')) {
            $query->where('target_system', $request->target_system);
        }

        $mappings = $query->orderBy('event_type')
            ->orderBy('target_system')
            ->orderByDesc('version')
            ->get()
            ->groupBy(fn ($m) => "{$m->event_type}:{$m->target_system}")
            ->map(fn ($group) => $group->first());

        return response()->json([
            'data' => $mappings->values(),
        ]);
    }

    /**
     * Cria novo mapeamento.
     *
     * POST /api/mappings
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event_type' => 'required|string|max:128',
            'target_system' => 'required|string|max:128',
            'mapping' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors(),
            ], 422);
        }

        $mapping = EventMapping::createVersion(
            $request->event_type,
            $request->target_system,
            $request->mapping
        );

        return response()->json([
            'data' => [
                'id' => $mapping->id,
                'event_type' => $mapping->event_type,
                'target_system' => $mapping->target_system,
                'version' => $mapping->version,
            ],
        ], 201);
    }

    /**
     * Exibe mapeamento atual.
     *
     * GET /api/mappings/{eventType}/{targetSystem}
     */
    public function show(string $eventType, string $targetSystem): JsonResponse
    {
        $mapping = EventMapping::forEvent($eventType, $targetSystem);

        if (!$mapping) {
            return response()->json([
                'error' => 'Mapping not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $mapping->id,
                'event_type' => $mapping->event_type,
                'target_system' => $mapping->target_system,
                'mapping' => $mapping->mapping_json,
                'version' => $mapping->version,
            ],
        ]);
    }

    /**
     * Lista versoes de um mapeamento.
     *
     * GET /api/mappings/{eventType}/{targetSystem}/versions
     */
    public function versions(string $eventType, string $targetSystem): JsonResponse
    {
        $versions = EventMapping::versions($eventType, $targetSystem);

        return response()->json([
            'data' => $versions->map(fn ($m) => [
                'id' => $m->id,
                'version' => $m->version,
                'mapping' => $m->mapping_json,
            ]),
        ]);
    }

    /**
     * Testa transformacao de payload.
     *
     * POST /api/mappings/test
     */
    public function test(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event_type' => 'required|string|max:128',
            'target_system' => 'required|string|max:128',
            'payload' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors(),
            ], 422);
        }

        $mapping = EventMapping::forEvent($request->event_type, $request->target_system);

        if (!$mapping) {
            return response()->json([
                'error' => 'Mapping not found',
            ], 404);
        }

        try {
            $transformed = $mapping->transform($request->payload);

            return response()->json([
                'data' => [
                    'original' => $request->payload,
                    'transformed' => $transformed,
                    'mapping_version' => $mapping->version,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Transformation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
