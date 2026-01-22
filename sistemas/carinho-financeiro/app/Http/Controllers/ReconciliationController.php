<?php

namespace App\Http\Controllers;

use App\Models\Reconciliation;
use App\Services\ReconciliationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReconciliationController extends Controller
{
    public function __construct(
        protected ReconciliationService $reconciliationService
    ) {}

    /**
     * Lista conciliações.
     */
    public function index(Request $request)
    {
        $query = Reconciliation::with(['status']);

        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->has('year')) {
            $query->where('period', 'like', "{$request->year}%");
        }

        $reconciliations = $query->orderBy('period', 'desc')->paginate(12);

        return $this->successResponse($reconciliations);
    }

    /**
     * Processa conciliação mensal.
     */
    public function process(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        try {
            $reconciliation = $this->reconciliationService->processMonthlyReconciliation(
                $request->year,
                $request->month
            );

            return $this->successResponse($reconciliation, 'Conciliação processada');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Exibe conciliação específica.
     */
    public function show(string $period)
    {
        $reconciliation = Reconciliation::forPeriod($period)->with(['status'])->firstOrFail();
        return $this->successResponse($reconciliation);
    }

    /**
     * Fecha conciliação.
     */
    public function close(Request $request, Reconciliation $reconciliation)
    {
        $request->validate([
            'closed_by' => 'nullable|string|max:100',
        ]);

        try {
            $reconciliation = $this->reconciliationService->closeReconciliation(
                $reconciliation,
                $request->closed_by
            );

            return $this->successResponse($reconciliation, 'Conciliação fechada');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Relatório de fluxo de caixa.
     */
    public function cashFlow(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->reconciliationService->getCashFlowReport(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return $this->successResponse($report);
    }

    /**
     * Indicadores financeiros.
     */
    public function indicators(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $indicators = $this->reconciliationService->getFinancialIndicators(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return $this->successResponse($indicators);
    }

    /**
     * Lista faturas não conciliadas.
     */
    public function unreconciledInvoices()
    {
        $invoices = $this->reconciliationService->getUnreconciledInvoices();
        return $this->successResponse($invoices);
    }

    /**
     * Lista pagamentos órfãos.
     */
    public function orphanPayments()
    {
        $payments = $this->reconciliationService->getOrphanPayments();
        return $this->successResponse($payments);
    }
}
