<?php

namespace App\Http\Controllers;

use App\Services\PartnershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para gestão de parcerias locais.
 */
class PartnershipController extends Controller
{
    public function __construct(
        private PartnershipService $service
    ) {}

    /**
     * Lista parcerias.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type_id', 'status_id', 'city', 'active']);

        $partnerships = $this->service->list($filters);

        return $this->success($partnerships, 'Parcerias carregadas');
    }

    /**
     * Cria nova parceria.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type_id' => 'required|integer|exists:domain_partnership_type,id',
            'status_id' => 'nullable|integer|exists:domain_partnership_status,id',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:32',
            'contact_email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:512',
            'city' => 'nullable|string|max:128',
            'state' => 'nullable|string|max:2',
            'notes' => 'nullable|string',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $partnership = $this->service->create($request->all());

            return $this->created($partnership->toArray(), 'Parceria criada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Exibe parceria com estatísticas.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $data = $this->service->get($id);

            return $this->success($data);
        } catch (\Throwable $e) {
            return $this->notFound('Parceria não encontrada');
        }
    }

    /**
     * Atualiza parceria.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'type_id' => 'nullable|integer|exists:domain_partnership_type,id',
            'status_id' => 'nullable|integer|exists:domain_partnership_status,id',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:32',
            'contact_email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:512',
            'city' => 'nullable|string|max:128',
            'state' => 'nullable|string|max:2',
            'notes' => 'nullable|string',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $partnership = $this->service->update($id, $request->all());

            return $this->success($partnership->toArray(), 'Parceria atualizada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Ativa parceria.
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $partnership = $this->service->activate($id);

            return $this->success($partnership->toArray(), 'Parceria ativada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Desativa parceria.
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $partnership = $this->service->deactivate($id);

            return $this->success($partnership->toArray(), 'Parceria desativada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Registra indicação de parceiro.
     */
    public function registerReferral(Request $request): JsonResponse
    {
        $request->validate([
            'referral_code' => 'required|string|max:32',
            'lead_id' => 'required|string|max:64',
            'lead_name' => 'nullable|string|max:255',
            'lead_phone' => 'nullable|string|max:32',
        ]);

        try {
            $referral = $this->service->registerReferral(
                $request->input('referral_code'),
                $request->input('lead_id'),
                $request->input('lead_name'),
                $request->input('lead_phone')
            );

            if (!$referral) {
                return $this->error('Código de parceria inválido ou parceria inativa');
            }

            return $this->created($referral->toArray(), 'Indicação registrada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Marca indicação como convertida.
     */
    public function convertReferral(Request $request, int $referralId): JsonResponse
    {
        $request->validate([
            'contract_value' => 'required|numeric|min:0',
        ]);

        try {
            $referral = $this->service->markReferralConverted(
                $referralId,
                $request->input('contract_value')
            );

            return $this->success($referral->toArray(), 'Indicação convertida');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Lista indicações de um parceiro.
     */
    public function listReferrals(Request $request, int $partnershipId): JsonResponse
    {
        $filters = $request->only(['converted', 'start_date', 'end_date']);

        $referrals = $this->service->listReferrals($partnershipId, $filters);

        return $this->success($referrals, 'Indicações carregadas');
    }

    /**
     * Lista comissões pendentes.
     */
    public function pendingCommissions(): JsonResponse
    {
        $commissions = $this->service->listPendingCommissions();

        return $this->success($commissions, 'Comissões pendentes carregadas');
    }

    /**
     * Marca comissão como paga.
     */
    public function payCommission(int $referralId): JsonResponse
    {
        try {
            $referral = $this->service->markCommissionPaid($referralId);

            return $this->success($referral->toArray(), 'Comissão marcada como paga');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Estatísticas de parcerias.
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
}
