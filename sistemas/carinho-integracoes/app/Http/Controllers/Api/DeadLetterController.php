<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeadLetter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para gerenciamento da Dead Letter Queue.
 */
class DeadLetterController extends Controller
{
    /**
     * Lista eventos na DLQ.
     *
     * GET /api/dlq
     */
    public function index(Request $request): JsonResponse
    {
        $query = DeadLetter::with(['event.status']);

        if ($request->has('event_type')) {
            $query->whereHas('event', function ($q) use ($request) {
                $q->where('event_type', $request->event_type);
            });
        }

        if ($request->has('days')) {
            $query->recent((int) $request->days);
        }

        $items = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => collect($items->items())->map(fn ($item) => [
                'id' => $item->id,
                'event_id' => $item->event_id,
                'event_type' => $item->event?->event_type,
                'source_system' => $item->event?->source_system,
                'reason' => $item->reason,
                'created_at' => $item->created_at->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * Exibe detalhes de item na DLQ.
     *
     * GET /api/dlq/{id}
     */
    public function show(int $id): JsonResponse
    {
        $item = DeadLetter::with(['event'])->find($id);

        if (!$item) {
            return response()->json([
                'error' => 'DLQ item not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $item->id,
                'event_id' => $item->event_id,
                'reason' => $item->reason,
                'created_at' => $item->created_at->toIso8601String(),
                'event' => $item->event ? [
                    'event_type' => $item->event->event_type,
                    'source_system' => $item->event->source_system,
                    'payload' => $item->event->payload_json,
                    'created_at' => $item->event->created_at->toIso8601String(),
                ] : null,
            ],
        ]);
    }

    /**
     * Tenta reprocessar evento da DLQ.
     *
     * POST /api/dlq/{id}/retry
     */
    public function retry(int $id): JsonResponse
    {
        $item = DeadLetter::find($id);

        if (!$item) {
            return response()->json([
                'error' => 'DLQ item not found',
            ], 404);
        }

        if ($item->retry()) {
            return response()->json([
                'message' => 'Event queued for retry',
                'event_id' => $item->event_id,
            ]);
        }

        return response()->json([
            'error' => 'Failed to retry event',
        ], 500);
    }

    /**
     * Arquiva item da DLQ.
     *
     * POST /api/dlq/{id}/archive
     */
    public function archive(int $id): JsonResponse
    {
        $item = DeadLetter::find($id);

        if (!$item) {
            return response()->json([
                'error' => 'DLQ item not found',
            ], 404);
        }

        $item->archive();

        return response()->json([
            'message' => 'DLQ item archived',
        ]);
    }

    /**
     * Remove item da DLQ.
     *
     * DELETE /api/dlq/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $item = DeadLetter::find($id);

        if (!$item) {
            return response()->json([
                'error' => 'DLQ item not found',
            ], 404);
        }

        $item->delete();

        return response()->json([
            'message' => 'DLQ item deleted',
        ]);
    }

    /**
     * Estatisticas da DLQ.
     *
     * GET /api/dlq/stats
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => DeadLetter::getStats(),
        ]);
    }

    /**
     * Reprocessa todos os eventos da DLQ em batch.
     *
     * POST /api/dlq/retry-all
     */
    public function retryAll(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 100);

        $items = DeadLetter::limit($limit)->get();
        $retried = 0;

        foreach ($items as $item) {
            if ($item->retry()) {
                $retried++;
            }
        }

        return response()->json([
            'message' => 'Batch retry completed',
            'retried' => $retried,
            'total' => $items->count(),
        ]);
    }
}
