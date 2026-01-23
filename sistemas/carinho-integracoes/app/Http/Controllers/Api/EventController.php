<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationEvent;
use App\Services\EventProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller para gerenciamento de eventos de integracao.
 */
class EventController extends Controller
{
    public function __construct(
        private EventProcessor $eventProcessor
    ) {}

    /**
     * Lista eventos com filtros.
     *
     * GET /api/events
     */
    public function index(Request $request): JsonResponse
    {
        $query = IntegrationEvent::query();

        if ($request->has('type')) {
            $query->where('event_type', $request->type);
        }

        if ($request->has('source')) {
            $query->where('source_system', $request->source);
        }

        if ($request->has('status')) {
            $query->where('status_id', $request->status);
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        $events = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    /**
     * Cria novo evento.
     *
     * POST /api/events
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event_type' => 'required|string|max:128',
            'source_system' => 'required|string|max:128',
            'payload' => 'required|array',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors(),
            ], 422);
        }

        $payload = $request->payload;

        if ($request->has('idempotency_key')) {
            $payload['idempotency_key'] = $request->idempotency_key;
        }

        $event = $this->eventProcessor->process(
            $request->event_type,
            $request->source_system,
            $payload
        );

        return response()->json([
            'data' => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'source_system' => $event->source_system,
                'status' => $event->status->code,
                'created_at' => $event->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Exibe detalhes de um evento.
     *
     * GET /api/events/{id}
     */
    public function show(int $id): JsonResponse
    {
        $event = IntegrationEvent::with(['status', 'deliveries.endpoint', 'retryEntry', 'deadLetter'])
            ->find($id);

        if (!$event) {
            return response()->json([
                'error' => 'Event not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'source_system' => $event->source_system,
                'payload' => $event->payload_json,
                'status' => $event->status->code,
                'created_at' => $event->created_at->toIso8601String(),
                'updated_at' => $event->updated_at?->toIso8601String(),
                'deliveries' => $event->deliveries->map(fn ($d) => [
                    'id' => $d->id,
                    'endpoint' => $d->endpoint->system_name,
                    'status' => $d->status->code,
                    'attempts' => $d->attempts,
                    'response_code' => $d->response_code,
                ]),
                'retry' => $event->retryEntry ? [
                    'attempts' => $event->retryEntry->attempts,
                    'next_retry_at' => $event->retryEntry->next_retry_at->toIso8601String(),
                ] : null,
                'dead_letter' => $event->deadLetter ? [
                    'reason' => $event->deadLetter->reason,
                    'created_at' => $event->deadLetter->created_at->toIso8601String(),
                ] : null,
            ],
        ]);
    }

    /**
     * Reprocessa um evento.
     *
     * POST /api/events/{id}/retry
     */
    public function retry(int $id): JsonResponse
    {
        $event = IntegrationEvent::find($id);

        if (!$event) {
            return response()->json([
                'error' => 'Event not found',
            ], 404);
        }

        // Reseta status
        $event->update([
            'status_id' => \App\Models\Domain\DomainEventStatus::PENDING,
        ]);

        // Remove de DLQ se estiver la
        if ($event->deadLetter) {
            $event->deadLetter->delete();
        }

        // Despacha para reprocessamento
        \App\Jobs\ProcessEvent::dispatch($event)->onQueue('integrations-retry');

        return response()->json([
            'message' => 'Event queued for retry',
            'event_id' => $event->id,
        ]);
    }

    /**
     * Estatisticas de eventos.
     *
     * GET /api/events/stats
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => $this->eventProcessor->getStats(),
        ]);
    }
}
