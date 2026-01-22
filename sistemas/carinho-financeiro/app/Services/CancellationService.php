<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Políticas de Cancelamento.
 *
 * Implementa as regras de cancelamento configuradas no banco de dados:
 * - Cancelamento sem custo: até 24h antes do serviço
 * - Reembolso parcial (50%): entre 12h e 24h antes
 * - Sem reembolso: menos de 6h antes
 * - Taxa administrativa: 5% para todos os cancelamentos com reembolso
 * - Cancelamento pelo cuidador: reembolso total ao cliente
 *
 * As configurações são obtidas do banco de dados via SettingService.
 */
class CancellationService
{
    public function __construct(
        protected SettingService $settingService
    ) {}
    /**
     * Processa cancelamento de fatura e calcula reembolso.
     */
    public function processInvoiceCancellation(
        Invoice $invoice,
        string $reason,
        ?Carbon $serviceDate = null,
        bool $isCaregiverCancellation = false
    ): array {
        $serviceDate = $serviceDate ?? $this->getServiceDateFromInvoice($invoice);
        $hoursUntilService = $this->calculateHoursUntilService($serviceDate);
        
        Log::info('Processando cancelamento', [
            'invoice_id' => $invoice->id,
            'service_date' => $serviceDate?->toDateTimeString(),
            'hours_until_service' => $hoursUntilService,
            'is_caregiver_cancellation' => $isCaregiverCancellation,
        ]);

        // Cancelamento por parte do cuidador: reembolso total
        if ($isCaregiverCancellation && config('financeiro.cancellation.caregiver_cancel_full_refund', true)) {
            return $this->processFullRefund($invoice, 'Cancelamento pelo cuidador');
        }

        // Determina tipo de reembolso baseado nas horas
        $policy = $this->determineRefundPolicy($hoursUntilService);

        return $this->calculateRefund($invoice, $policy, $reason);
    }

    /**
     * Determina a política de reembolso baseada nas horas até o serviço.
     */
    public function determineRefundPolicy(float $hoursUntilService): array
    {
        // Busca configurações do banco de dados
        $freeHours = $this->settingService->get(Setting::KEY_CANCEL_FREE_HOURS, 24);
        $partialHours = $this->settingService->get(Setting::KEY_CANCEL_PARTIAL_HOURS, 12);
        $partialPercent = $this->settingService->get(Setting::KEY_CANCEL_PARTIAL_PERCENT, 50);
        $noRefundHours = $this->settingService->get(Setting::KEY_CANCEL_NO_REFUND_HOURS, 6);
        $adminFee = $this->settingService->get(Setting::KEY_CANCEL_ADMIN_FEE, 5);

        // Cancelamento com antecedência suficiente: reembolso total
        if ($hoursUntilService >= $freeHours) {
            return [
                'type' => 'full_refund',
                'refund_percent' => 100,
                'admin_fee_percent' => 0,
                'message' => 'Cancelamento realizado com antecedência. Reembolso total.',
            ];
        }

        // Reembolso parcial
        if ($hoursUntilService >= $noRefundHours && $hoursUntilService < $partialHours) {
            return [
                'type' => 'partial_refund',
                'refund_percent' => $partialPercent,
                'admin_fee_percent' => $adminFee,
                'message' => 'Cancelamento com antecedência reduzida. Reembolso parcial aplicado.',
            ];
        }

        // Sem reembolso (muito próximo do serviço)
        if ($hoursUntilService < $noRefundHours) {
            return [
                'type' => 'no_refund',
                'refund_percent' => 0,
                'admin_fee_percent' => 0,
                'message' => 'Cancelamento muito próximo do serviço. Sem direito a reembolso.',
            ];
        }

        // Entre o período de reembolso parcial e cancelamento grátis
        return [
            'type' => 'partial_refund',
            'refund_percent' => $partialPercent,
            'admin_fee_percent' => $adminFee,
            'message' => 'Reembolso parcial aplicado conforme política de cancelamento.',
        ];
    }

    /**
     * Calcula o reembolso baseado na política.
     */
    protected function calculateRefund(Invoice $invoice, array $policy, string $reason): array
    {
        $totalAmount = (float) $invoice->total_amount;
        
        // Calcula valor do reembolso
        $refundPercent = $policy['refund_percent'];
        $baseRefund = $totalAmount * ($refundPercent / 100);

        // Aplica taxa administrativa (se houver reembolso)
        $adminFee = 0;
        if ($baseRefund > 0 && $policy['admin_fee_percent'] > 0) {
            $adminFee = $baseRefund * ($policy['admin_fee_percent'] / 100);
        }

        $finalRefund = max(0, $baseRefund - $adminFee);
        $cancellationFee = $totalAmount - $finalRefund;

        return [
            'policy_type' => $policy['type'],
            'original_amount' => $totalAmount,
            'refund_percent' => $refundPercent,
            'base_refund' => round($baseRefund, 2),
            'admin_fee_percent' => $policy['admin_fee_percent'],
            'admin_fee' => round($adminFee, 2),
            'refund_amount' => round($finalRefund, 2),
            'cancellation_fee' => round($cancellationFee, 2),
            'reason' => $reason,
            'message' => $policy['message'],
        ];
    }

    /**
     * Processa reembolso total.
     */
    protected function processFullRefund(Invoice $invoice, string $reason): array
    {
        $totalAmount = (float) $invoice->total_amount;

        return [
            'policy_type' => 'full_refund',
            'original_amount' => $totalAmount,
            'refund_percent' => 100,
            'base_refund' => $totalAmount,
            'admin_fee_percent' => 0,
            'admin_fee' => 0,
            'refund_amount' => $totalAmount,
            'cancellation_fee' => 0,
            'reason' => $reason,
            'message' => 'Reembolso total processado.',
        ];
    }

    /**
     * Calcula horas até o serviço.
     */
    protected function calculateHoursUntilService(?Carbon $serviceDate): float
    {
        if (!$serviceDate) {
            // Se não tiver data, assume muito próximo (sem reembolso)
            return 0;
        }

        $hours = now()->diffInMinutes($serviceDate, false) / 60;
        return max(0, $hours);
    }

    /**
     * Obtém data do serviço da fatura.
     */
    protected function getServiceDateFromInvoice(Invoice $invoice): ?Carbon
    {
        // Tenta obter do primeiro item
        $firstItem = $invoice->items()->orderBy('service_date', 'asc')->first();
        
        if ($firstItem && $firstItem->service_date) {
            return $firstItem->service_date;
        }

        // Fallback para period_start
        return $invoice->period_start;
    }

    /**
     * Valida se cancelamento é permitido.
     */
    public function canCancel(Invoice $invoice): array
    {
        if (!$invoice->canBeCanceled()) {
            return [
                'allowed' => false,
                'reason' => 'Fatura não pode ser cancelada no status atual.',
            ];
        }

        // Verifica se já houve pagamento
        $hasPaidPayment = $invoice->payments()
            ->where('status_id', \App\Models\DomainPaymentStatus::PAID)
            ->exists();

        if (!$hasPaidPayment) {
            return [
                'allowed' => true,
                'reason' => 'Cancelamento permitido. Nenhum pagamento realizado.',
                'requires_refund' => false,
            ];
        }

        $serviceDate = $this->getServiceDateFromInvoice($invoice);
        $policy = $this->determineRefundPolicy(
            $this->calculateHoursUntilService($serviceDate)
        );

        return [
            'allowed' => true,
            'reason' => $policy['message'],
            'requires_refund' => $hasPaidPayment && $policy['refund_percent'] > 0,
            'estimated_refund' => $invoice->total_amount * ($policy['refund_percent'] / 100),
            'policy' => $policy,
        ];
    }

    /**
     * Obtém texto explicativo da política.
     */
    public function getPolicyExplanation(): array
    {
        $freeHours = $this->settingService->get(Setting::KEY_CANCEL_FREE_HOURS, 24);
        $partialHours = $this->settingService->get(Setting::KEY_CANCEL_PARTIAL_HOURS, 12);
        $partialPercent = $this->settingService->get(Setting::KEY_CANCEL_PARTIAL_PERCENT, 50);
        $noRefundHours = $this->settingService->get(Setting::KEY_CANCEL_NO_REFUND_HOURS, 6);
        $adminFee = $this->settingService->get(Setting::KEY_CANCEL_ADMIN_FEE, 5);

        return [
            'title' => 'Política de Cancelamento',
            'rules' => [
                [
                    'period' => "Mais de {$freeHours}h antes",
                    'refund' => 'Reembolso total (100%)',
                ],
                [
                    'period' => "Entre {$noRefundHours}h e {$partialHours}h antes",
                    'refund' => "Reembolso parcial ({$partialPercent}%)",
                ],
                [
                    'period' => "Menos de {$noRefundHours}h antes",
                    'refund' => 'Sem direito a reembolso',
                ],
            ],
            'notes' => [
                "Taxa administrativa de {$adminFee}% aplica-se aos reembolsos parciais.",
                'Cancelamentos por parte do cuidador garantem reembolso total ao cliente.',
            ],
        ];
    }
}
