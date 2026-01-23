<?php

namespace App\Http\Controllers;

use App\Http\Resources\PricePlanResource;
use App\Models\DomainServiceType;
use App\Models\PricePlan;
use App\Models\PriceRule;
use App\Services\CancellationService;
use App\Services\PricingService;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function __construct(
        protected PricingService $pricingService,
        protected CancellationService $cancellationService
    ) {}

    /**
     * Calcula preço para um serviço.
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'service_type_id' => 'required|integer|exists:domain_service_type,id',
            'qty' => 'required|numeric|min:0.01',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date',
            'region_code' => 'nullable|string|max:10',
            'is_weekend' => 'nullable|boolean',
            'is_holiday' => 'nullable|boolean',
            'is_monthly_package' => 'nullable|boolean',
        ]);

        $result = $this->pricingService->calculatePrice($request->all());

        return $this->successResponse($result);
    }

    /**
     * Simula preços para todos os tipos de serviço.
     */
    public function simulate(Request $request)
    {
        $request->validate([
            'hours' => 'required|numeric|min:1',
            'is_weekend' => 'nullable|boolean',
            'is_holiday' => 'nullable|boolean',
        ]);

        $results = $this->pricingService->simulatePrices(
            $request->hours,
            $request->only(['is_weekend', 'is_holiday'])
        );

        return $this->successResponse($results);
    }

    /**
     * Calcula margem de um preço.
     */
    public function margin(Request $request)
    {
        $request->validate([
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
        ]);

        $result = $this->pricingService->calculateMargin(
            $request->price,
            $request->cost
        );

        return $this->successResponse($result);
    }

    /**
     * Calcula preço mínimo viável.
     */
    public function minimumViable(Request $request)
    {
        $request->validate([
            'caregiver_cost' => 'required|numeric|min:0',
        ]);

        $minPrice = $this->pricingService->calculateMinimumViablePrice(
            $request->caregiver_cost
        );

        return $this->successResponse([
            'caregiver_cost' => $request->caregiver_cost,
            'minimum_viable_price' => $minPrice,
            'target_margin' => config('financeiro.margin.target'),
        ]);
    }

    /**
     * Lista planos de preço.
     */
    public function plans(Request $request)
    {
        $query = PricePlan::with(['serviceType', 'rules']);

        if ($request->has('service_type_id')) {
            $query->byServiceType($request->service_type_id);
        }

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($request->has('region_code')) {
            $query->byRegion($request->region_code);
        }

        $plans = $query->get();

        return PricePlanResource::collection($plans);
    }

    /**
     * Cria plano de preço.
     */
    public function storePlan(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:128',
            'service_type_id' => 'required|integer|exists:domain_service_type,id',
            'base_price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'min_hours' => 'nullable|integer|min:1',
            'max_hours' => 'nullable|integer',
            'region_code' => 'nullable|string|max:10',
            'active' => 'nullable|boolean',
        ]);

        $plan = PricePlan::create($request->all());

        return $this->createdResponse(
            new PricePlanResource($plan),
            'Plano criado'
        );
    }

    /**
     * Atualiza plano de preço.
     */
    public function updatePlan(Request $request, PricePlan $plan)
    {
        $request->validate([
            'name' => 'sometimes|string|max:128',
            'base_price' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'min_hours' => 'nullable|integer|min:1',
            'max_hours' => 'nullable|integer',
            'active' => 'nullable|boolean',
        ]);

        $plan->update($request->all());

        return $this->successResponse(
            new PricePlanResource($plan->fresh()),
            'Plano atualizado'
        );
    }

    /**
     * Adiciona regra a um plano.
     */
    public function addRule(Request $request, PricePlan $plan)
    {
        $request->validate([
            'rule_type' => 'required|string|max:64',
            'value' => 'required|numeric',
            'name' => 'nullable|string|max:128',
            'conditions_json' => 'nullable|array',
            'priority' => 'nullable|integer',
        ]);

        $rule = PriceRule::create([
            'plan_id' => $plan->id,
            ...$request->all(),
        ]);

        return $this->createdResponse($rule, 'Regra adicionada');
    }

    /**
     * Remove regra de um plano.
     */
    public function removeRule(PricePlan $plan, PriceRule $rule)
    {
        if ($rule->plan_id !== $plan->id) {
            return $this->errorResponse('Regra não pertence a este plano', 422);
        }

        $rule->delete();

        return $this->successResponse(null, 'Regra removida');
    }

    /**
     * Lista tipos de serviço.
     */
    public function serviceTypes()
    {
        $types = DomainServiceType::all()->map(function ($type) {
            return [
                'id' => $type->id,
                'code' => $type->code,
                'label' => $type->label,
                'caregiver_commission_percent' => $type->getCaregiverCommissionPercent(),
            ];
        });

        return $this->successResponse($types);
    }

    /**
     * Obtém política de cancelamento.
     */
    public function cancellationPolicy()
    {
        $policy = $this->cancellationService->getPolicyExplanation();
        return $this->successResponse($policy);
    }

    /**
     * Simula cancelamento (para preview antes de confirmar).
     */
    public function simulateCancellation(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'service_date' => 'nullable|date',
        ]);

        $invoice = \App\Models\Invoice::findOrFail($request->invoice_id);

        $canCancel = $this->cancellationService->canCancel($invoice);

        if (!$canCancel['allowed']) {
            return $this->errorResponse($canCancel['reason'], 422);
        }

        $serviceDate = $request->service_date 
            ? \Carbon\Carbon::parse($request->service_date) 
            : null;

        $simulation = $this->cancellationService->processInvoiceCancellation(
            $invoice,
            'Simulação',
            $serviceDate
        );

        return $this->successResponse([
            'can_cancel' => $canCancel,
            'simulation' => $simulation,
        ]);
    }

    /**
     * Obtém configurações de comissão.
     */
    public function commissionConfig()
    {
        $config = config('financeiro.commission');
        
        return $this->successResponse([
            'default_caregiver_percent' => $config['caregiver_percent'],
            'default_company_percent' => $config['company_percent'],
            'by_service_type' => $config['by_service_type'],
            'rating_bonus' => $config['rating_bonus'],
            'tenure_bonus' => $config['tenure_bonus'],
        ]);
    }

    /**
     * Obtém configurações de pagamento.
     */
    public function paymentConfig()
    {
        $config = config('financeiro.payment');
        
        return $this->successResponse([
            'type' => $config['type'],
            'advance_hours' => $config['advance_hours'],
            'grace_period_days' => $config['grace_period_days'],
            'late_fee_daily' => $config['late_fee_daily'],
            'late_penalty' => $config['late_penalty'],
        ]);
    }
}
