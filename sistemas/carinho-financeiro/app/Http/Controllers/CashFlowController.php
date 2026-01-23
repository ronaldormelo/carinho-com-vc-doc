<?php

namespace App\Http\Controllers;

use App\Services\CashFlowService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller de Fluxo de Caixa.
 */
class CashFlowController extends Controller
{
    public function __construct(
        protected CashFlowService $cashFlowService
    ) {}

    /**
     * Obtém transações recentes.
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 50);
        $transactions = $this->cashFlowService->getRecentTransactions($limit);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Obtém saldo do período.
     */
    public function balance(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $balance = $this->cashFlowService->getPeriodBalance(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json([
            'success' => true,
            'data' => $balance,
        ]);
    }

    /**
     * Obtém fluxo de caixa diário.
     */
    public function daily(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $flow = $this->cashFlowService->getDailyCashFlow(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json([
            'success' => true,
            'data' => $flow,
        ]);
    }

    /**
     * Obtém fluxo de caixa por categoria.
     */
    public function byCategory(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $flow = $this->cashFlowService->getCashFlowByCategory(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json([
            'success' => true,
            'data' => $flow,
        ]);
    }

    /**
     * Obtém previsão de fluxo de caixa.
     */
    public function forecast(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $forecast = $this->cashFlowService->getCashFlowForecast($days);

        return response()->json([
            'success' => true,
            'data' => $forecast,
        ]);
    }

    /**
     * Registra transação manual.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_date' => 'required|date',
            'type_id' => 'required|integer',
            'category_id' => 'required|integer',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'direction' => 'required|in:in,out',
            'competence_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $transaction = $request->direction === 'in'
            ? $this->cashFlowService->registerIncome($request->all())
            : $this->cashFlowService->registerExpense($request->all());

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ], 201);
    }
}
