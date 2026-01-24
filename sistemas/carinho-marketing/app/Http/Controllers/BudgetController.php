<?php

namespace App\Http\Controllers;

use App\Services\BudgetControlService;
use App\Services\CampaignApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para controle de orçamento e aprovações.
 */
class BudgetController extends Controller
{
    public function __construct(
        private BudgetControlService $budgetService,
        private CampaignApprovalService $approvalService
    ) {}

    /**
     * Obtém resumo de orçamento.
     */
    public function summary(): JsonResponse
    {
        $summary = $this->budgetService->getBudgetSummary();

        return $this->success($summary, 'Resumo de orçamento carregado');
    }

    /**
     * Define limite para campanha.
     */
    public function setLimit(Request $request, int $campaignId): JsonResponse
    {
        $request->validate([
            'daily_limit' => 'nullable|numeric|min:0',
            'monthly_limit' => 'nullable|numeric|min:0',
            'total_limit' => 'nullable|numeric|min:0',
            'auto_pause_enabled' => 'nullable|boolean',
            'alert_threshold_70' => 'nullable|boolean',
            'alert_threshold_90' => 'nullable|boolean',
            'alert_threshold_100' => 'nullable|boolean',
        ]);

        try {
            $limit = $this->budgetService->setLimit($campaignId, $request->all());

            return $this->success($limit->toArray(), 'Limite definido');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Define limite global.
     */
    public function setGlobalLimit(Request $request): JsonResponse
    {
        $request->validate([
            'daily_limit' => 'nullable|numeric|min:0',
            'monthly_limit' => 'nullable|numeric|min:0',
            'total_limit' => 'nullable|numeric|min:0',
            'auto_pause_enabled' => 'nullable|boolean',
            'alert_threshold_70' => 'nullable|boolean',
            'alert_threshold_90' => 'nullable|boolean',
            'alert_threshold_100' => 'nullable|boolean',
        ]);

        try {
            $limit = $this->budgetService->setGlobalLimit($request->all());

            return $this->success($limit->toArray(), 'Limite global definido');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Obtém limite de campanha.
     */
    public function getLimit(int $campaignId): JsonResponse
    {
        $limit = $this->budgetService->getLimit($campaignId);

        return $this->success($limit ? $limit->toArray() : null, 'Limite carregado');
    }

    /**
     * Obtém limite global.
     */
    public function getGlobalLimit(): JsonResponse
    {
        $limit = $this->budgetService->getGlobalLimit();

        return $this->success($limit ? $limit->toArray() : null, 'Limite global carregado');
    }

    /**
     * Lista alertas não reconhecidos.
     */
    public function alerts(): JsonResponse
    {
        $alerts = $this->budgetService->getUnacknowledgedAlerts();

        return $this->success($alerts, 'Alertas carregados');
    }

    /**
     * Reconhece alerta.
     */
    public function acknowledgeAlert(int $alertId): JsonResponse
    {
        try {
            $alert = $this->budgetService->acknowledgeAlert($alertId);

            return $this->success($alert->toArray(), 'Alerta reconhecido');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Verifica e dispara alertas.
     */
    public function checkAlerts(): JsonResponse
    {
        $alerts = $this->budgetService->checkAndAlert();

        return $this->success([
            'alerts_triggered' => count($alerts),
            'alerts' => $alerts,
        ], 'Verificação de alertas concluída');
    }

    /**
     * Solicita aprovação de orçamento.
     */
    public function requestApproval(Request $request): JsonResponse
    {
        $request->validate([
            'campaign_id' => 'required|integer|exists:campaigns,id',
            'budget' => 'required|numeric|min:0',
            'requested_by' => 'required|integer',
            'justification' => 'nullable|string',
        ]);

        try {
            $approval = $this->approvalService->requestApproval(
                $request->input('campaign_id'),
                $request->input('budget'),
                $request->input('requested_by'),
                $request->input('justification')
            );

            return $this->created($approval->toArray(), 'Solicitação de aprovação criada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Lista aprovações pendentes.
     */
    public function pendingApprovals(): JsonResponse
    {
        $approvals = $this->approvalService->listPending();

        return $this->success($approvals, 'Aprovações pendentes carregadas');
    }

    /**
     * Aprova solicitação.
     */
    public function approve(Request $request, int $approvalId): JsonResponse
    {
        $request->validate([
            'approved_by' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        try {
            $approval = $this->approvalService->approve(
                $approvalId,
                $request->input('approved_by'),
                $request->input('notes'),
                [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            return $this->success($approval->toArray(), 'Solicitação aprovada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Rejeita solicitação.
     */
    public function reject(Request $request, int $approvalId): JsonResponse
    {
        $request->validate([
            'rejected_by' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        try {
            $approval = $this->approvalService->reject(
                $approvalId,
                $request->input('rejected_by'),
                $request->input('notes'),
                [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            return $this->success($approval->toArray(), 'Solicitação rejeitada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Verifica se campanha pode ser ativada.
     */
    public function canActivate(int $campaignId): JsonResponse
    {
        $canActivate = $this->approvalService->canActivate($campaignId);

        return $this->success([
            'can_activate' => $canActivate,
            'auto_approval_limit' => $this->approvalService->getAutoApprovalLimit(),
        ]);
    }

    /**
     * Histórico de aprovações da campanha.
     */
    public function approvalHistory(int $campaignId): JsonResponse
    {
        $history = $this->approvalService->getHistory($campaignId);

        return $this->success($history, 'Histórico de aprovações carregado');
    }
}
