<?php

namespace App\Http\Controllers;

use App\Http\Resources\PayoutResource;
use App\Models\Payout;
use App\Services\PayoutService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function __construct(
        protected PayoutService $payoutService
    ) {}

    /**
     * Lista repasses com filtros.
     */
    public function index(Request $request)
    {
        $query = Payout::with(['status', 'items']);

        if ($request->has('caregiver_id')) {
            $query->forCaregiver($request->caregiver_id);
        }

        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->has('period_start') && $request->has('period_end')) {
            $query->forPeriod($request->period_start, $request->period_end);
        }

        $sortField = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        $perPage = min($request->get('per_page', 15), 100);
        $payouts = $query->paginate($perPage);

        return PayoutResource::collection($payouts);
    }

    /**
     * Cria repasse para um cuidador.
     */
    public function store(Request $request)
    {
        $request->validate([
            'caregiver_id' => 'required|integer',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        try {
            $payout = $this->payoutService->createPayout(
                $request->caregiver_id,
                Carbon::parse($request->period_start),
                Carbon::parse($request->period_end)
            );

            return $this->createdResponse(
                new PayoutResource($payout),
                'Repasse criado'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Exibe repasse específico.
     */
    public function show(Payout $payout)
    {
        $payout->load(['status', 'items', 'bankAccount']);
        return new PayoutResource($payout);
    }

    /**
     * Processa transferência do repasse.
     */
    public function process(Payout $payout)
    {
        try {
            $payout = $this->payoutService->processPayoutTransfer($payout);

            return $this->successResponse(
                new PayoutResource($payout),
                'Repasse processado com sucesso'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Cancela repasse.
     */
    public function cancel(Request $request, Payout $payout)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $payout->markAsCanceled($request->reason);

            return $this->successResponse(
                new PayoutResource($payout),
                'Repasse cancelado'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Gera repasses para um período.
     */
    public function generateForPeriod(Request $request)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        try {
            $result = $this->payoutService->generatePayoutsForPeriod(
                Carbon::parse($request->period_start),
                Carbon::parse($request->period_end)
            );

            return $this->successResponse($result, 'Repasses gerados');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Processa todos os repasses pendentes.
     */
    public function processAll()
    {
        try {
            $result = $this->payoutService->processAllPendingPayouts();

            return $this->successResponse($result, 'Processamento concluído');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Resumo de repasses de um cuidador.
     */
    public function caregiverSummary(int $caregiverId)
    {
        $summary = $this->payoutService->getCaregiverPayoutSummary($caregiverId);
        return $this->successResponse($summary);
    }

    /**
     * Detalhes de comissão por tipo de serviço.
     */
    public function commissionDetails(int $serviceTypeId)
    {
        $details = $this->payoutService->getCommissionDetails($serviceTypeId);
        return $this->successResponse($details);
    }

    /**
     * Lista repasses prontos para processar.
     */
    public function readyToProcess()
    {
        $payouts = Payout::with(['status'])
            ->readyToProcess()
            ->orderBy('created_at', 'asc')
            ->get();

        return PayoutResource::collection($payouts);
    }
}
