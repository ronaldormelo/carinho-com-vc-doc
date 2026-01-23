<?php

namespace App\Http\Controllers;

use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para gestão de indicações de clientes.
 */
class ReferralController extends Controller
{
    public function __construct(
        private ReferralService $service
    ) {}

    /**
     * Cria código de indicação para cliente.
     */
    public function createCode(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|string|max:64',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:32',
        ]);

        try {
            $referral = $this->service->createReferralCode(
                $request->input('customer_id'),
                $request->input('customer_name'),
                $request->input('customer_phone')
            );

            return $this->success([
                'referral_code' => $referral->referral_code,
                'referrer_customer_id' => $referral->referrer_customer_id,
            ], 'Código de indicação criado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Registra lead indicado.
     */
    public function registerReferred(Request $request): JsonResponse
    {
        $request->validate([
            'referral_code' => 'required|string|max:32',
            'lead_id' => 'required|string|max:64',
            'lead_name' => 'nullable|string|max:255',
            'lead_phone' => 'nullable|string|max:32',
        ]);

        try {
            $referral = $this->service->registerReferred(
                $request->input('referral_code'),
                $request->input('lead_id'),
                $request->input('lead_name'),
                $request->input('lead_phone')
            );

            if (!$referral) {
                return $this->error('Código de indicação inválido ou limite excedido');
            }

            return $this->created($referral->toArray(), 'Lead indicado registrado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Marca indicação como convertida.
     */
    public function convert(Request $request, int $referralId): JsonResponse
    {
        $request->validate([
            'contract_value' => 'required|numeric|min:0',
        ]);

        try {
            $referral = $this->service->markAsConverted(
                $referralId,
                $request->input('contract_value')
            );

            return $this->success($referral->toArray(), 'Indicação convertida');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Aplica benefício ao cliente que indicou.
     */
    public function applyBenefit(int $referralId): JsonResponse
    {
        try {
            $referral = $this->service->applyBenefit($referralId);

            return $this->success($referral->toArray(), 'Benefício aplicado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Obtém informações de indicação do cliente.
     */
    public function customerInfo(string $customerId): JsonResponse
    {
        $info = $this->service->getCustomerReferralInfo($customerId);

        return $this->success($info, 'Informações de indicação carregadas');
    }

    /**
     * Lista indicações do cliente.
     */
    public function customerReferrals(string $customerId): JsonResponse
    {
        $referrals = $this->service->listCustomerReferrals($customerId);

        return $this->success($referrals, 'Indicações carregadas');
    }

    /**
     * Lista benefícios pendentes.
     */
    public function pendingBenefits(): JsonResponse
    {
        $benefits = $this->service->listPendingBenefits();

        return $this->success($benefits, 'Benefícios pendentes carregados');
    }

    /**
     * Estatísticas do programa de indicação.
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $stats = $this->service->getStats(
            $request->input('start_date'),
            $request->input('end_date')
        );

        return $this->success($stats, 'Estatísticas carregadas');
    }

    /**
     * Obtém configuração do programa.
     */
    public function programConfig(): JsonResponse
    {
        $config = $this->service->getProgramConfig();

        return $this->success($config ? $config->toArray() : null, 'Configuração carregada');
    }

    /**
     * Atualiza configuração do programa.
     */
    public function updateProgramConfig(Request $request): JsonResponse
    {
        $request->validate([
            'is_active' => 'nullable|boolean',
            'benefit_type' => 'nullable|string|in:discount,bonus,gift',
            'referrer_benefit_value' => 'nullable|numeric|min:0',
            'referred_benefit_value' => 'nullable|numeric|min:0',
            'min_contract_value' => 'nullable|integer|min:0',
            'max_referrals_per_month' => 'nullable|integer|min:1',
            'terms' => 'nullable|string',
        ]);

        try {
            $config = $this->service->updateProgramConfig($request->all());

            return $this->success($config->toArray(), 'Configuração atualizada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}
