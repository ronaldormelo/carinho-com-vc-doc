<?php

namespace App\Http\Controllers;

use App\Services\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller de Aprovações.
 */
class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService
    ) {}

    /**
     * Lista aprovações pendentes.
     */
    public function pending(Request $request): JsonResponse
    {
        $operationType = $request->get('operation_type');
        $approvals = $this->approvalService->getPendingApprovals($operationType);

        return response()->json([
            'success' => true,
            'data' => $approvals,
        ]);
    }

    /**
     * Verifica se operação requer aprovação.
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'operation_type' => 'required|string|in:discount,refund,payout,payable',
            'amount' => 'required|numeric|min:0',
        ]);

        $result = $this->approvalService->requiresApproval(
            $request->operation_type,
            $request->amount
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Cria solicitação de aprovação.
     */
    public function request(Request $request): JsonResponse
    {
        $request->validate([
            'operation_type' => 'required|string|in:discount,refund,payout,payable',
            'operation_id' => 'required|integer',
            'amount' => 'required|numeric|min:0',
            'requested_by' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        $approval = $this->approvalService->createApprovalRequest(
            $request->operation_type,
            $request->operation_id,
            $request->amount,
            $request->requested_by,
            $request->reason
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $approval->id,
                'status' => $approval->status->code,
                'is_approved' => $approval->isApproved(),
                'requires_manual_approval' => $approval->isPending(),
                'expires_at' => $approval->expires_at?->toDateTimeString(),
            ],
        ], 201);
    }

    /**
     * Aprova uma solicitação.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'decided_by' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $approval = $this->approvalService->approve(
                $id,
                $request->decided_by,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $approval->id,
                    'status' => $approval->status->code,
                    'decided_at' => $approval->decided_at->toDateTimeString(),
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
     * Rejeita uma solicitação.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'decided_by' => 'required|string',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $approval = $this->approvalService->reject(
                $id,
                $request->decided_by,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $approval->id,
                    'status' => $approval->status->code,
                    'decided_at' => $approval->decided_at->toDateTimeString(),
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
     * Obtém status de aprovação de uma operação.
     */
    public function status(Request $request): JsonResponse
    {
        $request->validate([
            'operation_type' => 'required|string',
            'operation_id' => 'required|integer',
        ]);

        $status = $this->approvalService->getOperationApprovalStatus(
            $request->operation_type,
            $request->operation_id
        );

        if (!$status) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_approval' => false,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => array_merge(['has_approval' => true], $status),
        ]);
    }

    /**
     * Obtém métricas de aprovação.
     */
    public function metrics(Request $request): JsonResponse
    {
        $daysBack = $request->get('days', 30);
        $metrics = $this->approvalService->getApprovalMetrics($daysBack);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }
}
