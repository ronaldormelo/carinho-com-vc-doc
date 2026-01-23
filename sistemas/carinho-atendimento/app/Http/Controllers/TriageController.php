<?php

namespace App\Http\Controllers;

use App\Services\TriageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller de triagem de atendimento.
 *
 * Gerencia o checklist padronizado de qualificacao de leads,
 * garantindo coleta consistente de informacoes essenciais.
 */
class TriageController extends Controller
{
    public function __construct(private TriageService $triageService)
    {
    }

    /**
     * Obtem o checklist de triagem padrao.
     */
    public function checklist(): JsonResponse
    {
        return response()->json([
            'checklist' => $this->triageService->getChecklist(),
        ]);
    }

    /**
     * Obtem o script de atendimento formatado.
     */
    public function script(): JsonResponse
    {
        return response()->json([
            'script' => $this->triageService->getScript(),
        ]);
    }

    /**
     * Obtem o status da triagem de uma conversa.
     */
    public function status(int $conversation): JsonResponse
    {
        return response()->json(
            $this->triageService->getTriageStatus($conversation)
        );
    }

    /**
     * Registra resposta de um item da triagem.
     */
    public function saveResponse(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'checklist_id' => ['required', 'integer'],
            'response' => ['required', 'string', 'max:1000'],
            'agent_id' => ['nullable', 'integer'],
        ]);

        $id = $this->triageService->saveResponse(
            $conversation,
            $validated['checklist_id'],
            $validated['response'],
            $validated['agent_id'] ?? null
        );

        return response()->json([
            'id' => $id,
            'status' => $this->triageService->getTriageStatus($conversation),
        ]);
    }

    /**
     * Salva multiplas respostas de uma vez.
     */
    public function saveResponses(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'responses' => ['required', 'array'],
            'responses.*' => ['nullable', 'string', 'max:1000'],
            'agent_id' => ['nullable', 'integer'],
        ]);

        $this->triageService->saveResponses(
            $conversation,
            $validated['responses'],
            $validated['agent_id'] ?? null
        );

        return response()->json([
            'status' => $this->triageService->getTriageStatus($conversation),
        ]);
    }

    /**
     * Obtem resumo da triagem para CRM.
     */
    public function summary(int $conversation): JsonResponse
    {
        return response()->json([
            'summary' => $this->triageService->getTriageSummary($conversation),
        ]);
    }
}
