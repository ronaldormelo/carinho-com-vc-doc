<?php

namespace App\Http\Controllers;

use App\Services\ProvisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller de Provisões.
 */
class ProvisionController extends Controller
{
    public function __construct(
        protected ProvisionService $provisionService
    ) {}

    /**
     * Calcula PCLD para um período.
     */
    public function calculatePCLD(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $provision = $this->provisionService->calculateMonthlyPCLD(
            $request->year,
            $request->month,
            $request->user_id ?? 'api'
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $provision->id,
                'period' => $provision->period,
                'calculated_amount' => $provision->calculated_amount,
                'effective_amount' => $provision->effective_amount,
                'used_amount' => $provision->used_amount,
                'balance' => $provision->balance,
                'calculation_base' => $provision->calculation_base,
            ],
        ]);
    }

    /**
     * Recalcula PCLD existente.
     */
    public function recalculatePCLD(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $provision = $this->provisionService->recalculatePCLD(
            $request->period,
            $request->user_id ?? 'api'
        );

        if (!$provision) {
            return response()->json([
                'success' => false,
                'message' => 'Provisão não encontrada para o período',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $provision->id,
                'period' => $provision->period,
                'calculated_amount' => $provision->calculated_amount,
                'balance' => $provision->balance,
            ],
        ]);
    }

    /**
     * Registra baixa contra provisão.
     */
    public function writeOff(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $provision = $this->provisionService->writeOff(
                $request->period,
                $request->amount,
                $request->reason,
                $request->user_id ?? 'api'
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $provision->period,
                    'written_off' => $request->amount,
                    'remaining_balance' => $provision->balance,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtém resumo de provisões.
     */
    public function summary(Request $request): JsonResponse
    {
        $monthsBack = $request->get('months', 12);
        $summary = $this->provisionService->getProvisionSummary($monthsBack);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Obtém análise de efetividade.
     */
    public function effectiveness(Request $request): JsonResponse
    {
        $year = $request->get('year', now()->year);
        $analysis = $this->provisionService->getProvisionEffectivenessAnalysis($year);

        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }
}
