<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Models\Domain\DomainEndpointStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller para gerenciamento de endpoints de webhook.
 */
class WebhookEndpointController extends Controller
{
    /**
     * Lista endpoints.
     *
     * GET /api/endpoints
     */
    public function index(Request $request): JsonResponse
    {
        $query = WebhookEndpoint::with('status');

        if ($request->has('system')) {
            $query->where('system_name', $request->system);
        }

        if ($request->boolean('active_only', false)) {
            $query->active();
        }

        $endpoints = $query->orderBy('system_name')->get();

        return response()->json([
            'data' => $endpoints->map(fn ($e) => [
                'id' => $e->id,
                'system_name' => $e->system_name,
                'url' => $e->url,
                'status' => $e->status->code,
                'created_at' => $e->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Cria novo endpoint.
     *
     * POST /api/endpoints
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'system_name' => 'required|string|max:128',
            'url' => 'required|url|max:512',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors(),
            ], 422);
        }

        $endpoint = WebhookEndpoint::createWithSecret(
            $request->system_name,
            $request->url
        );

        return response()->json([
            'data' => [
                'id' => $endpoint->id,
                'system_name' => $endpoint->system_name,
                'url' => $endpoint->url,
                'secret' => $endpoint->secret, // Exibe apenas na criacao
                'status' => 'active',
            ],
            'message' => 'Endpoint created. Save the secret, it will not be shown again.',
        ], 201);
    }

    /**
     * Exibe endpoint.
     *
     * GET /api/endpoints/{id}
     */
    public function show(int $id): JsonResponse
    {
        $endpoint = WebhookEndpoint::with(['status', 'deliveries' => function ($q) {
            $q->orderByDesc('id')->limit(10);
        }])->find($id);

        if (!$endpoint) {
            return response()->json([
                'error' => 'Endpoint not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $endpoint->id,
                'system_name' => $endpoint->system_name,
                'url' => $endpoint->url,
                'status' => $endpoint->status->code,
                'created_at' => $endpoint->created_at->toIso8601String(),
                'updated_at' => $endpoint->updated_at?->toIso8601String(),
                'recent_deliveries' => $endpoint->deliveries->map(fn ($d) => [
                    'id' => $d->id,
                    'event_id' => $d->event_id,
                    'status' => $d->status->code ?? 'unknown',
                    'attempts' => $d->attempts,
                    'response_code' => $d->response_code,
                    'last_attempt_at' => $d->last_attempt_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * Atualiza endpoint.
     *
     * PUT /api/endpoints/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $endpoint = WebhookEndpoint::find($id);

        if (!$endpoint) {
            return response()->json([
                'error' => 'Endpoint not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'url' => 'sometimes|url|max:512',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors(),
            ], 422);
        }

        if ($request->has('url')) {
            $endpoint->update(['url' => $request->url]);
        }

        return response()->json([
            'data' => [
                'id' => $endpoint->id,
                'system_name' => $endpoint->system_name,
                'url' => $endpoint->url,
                'status' => $endpoint->status->code,
            ],
        ]);
    }

    /**
     * Ativa endpoint.
     *
     * POST /api/endpoints/{id}/activate
     */
    public function activate(int $id): JsonResponse
    {
        $endpoint = WebhookEndpoint::find($id);

        if (!$endpoint) {
            return response()->json([
                'error' => 'Endpoint not found',
            ], 404);
        }

        $endpoint->activate();

        return response()->json([
            'message' => 'Endpoint activated',
        ]);
    }

    /**
     * Desativa endpoint.
     *
     * POST /api/endpoints/{id}/deactivate
     */
    public function deactivate(int $id): JsonResponse
    {
        $endpoint = WebhookEndpoint::find($id);

        if (!$endpoint) {
            return response()->json([
                'error' => 'Endpoint not found',
            ], 404);
        }

        $endpoint->deactivate();

        return response()->json([
            'message' => 'Endpoint deactivated',
        ]);
    }

    /**
     * Rotaciona secret do endpoint.
     *
     * POST /api/endpoints/{id}/rotate-secret
     */
    public function rotateSecret(int $id): JsonResponse
    {
        $endpoint = WebhookEndpoint::find($id);

        if (!$endpoint) {
            return response()->json([
                'error' => 'Endpoint not found',
            ], 404);
        }

        $newSecret = \Illuminate\Support\Str::random(64);
        $endpoint->update(['secret' => $newSecret]);

        return response()->json([
            'data' => [
                'id' => $endpoint->id,
                'secret' => $newSecret,
            ],
            'message' => 'Secret rotated. Save the new secret, it will not be shown again.',
        ]);
    }
}
