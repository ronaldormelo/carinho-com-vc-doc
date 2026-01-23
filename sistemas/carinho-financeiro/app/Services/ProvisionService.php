<?php

namespace App\Services;

use App\Models\DomainInvoiceStatus;
use App\Models\Invoice;
use App\Models\Provision;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Provisões Financeiras.
 *
 * Responsável por:
 * - Calcular e registrar PCLD (Provisão para Créditos de Liquidação Duvidosa)
 * - Gerenciar provisões mensais
 * - Processar baixas contra provisões
 * - Reverter provisões não utilizadas
 *
 * A PCLD é uma prática contábil consolidada que reconhece antecipadamente
 * as perdas estimadas com inadimplência, permitindo uma visão mais
 * realista da situação financeira da empresa.
 */
class ProvisionService
{
    /**
     * Percentuais de provisão por faixa de aging.
     * Baseado em práticas tradicionais de mercado.
     */
    protected const PCLD_RATES = [
        '1_30' => 0.03,    // 3% para 1-30 dias
        '31_60' => 0.10,   // 10% para 31-60 dias
        '61_90' => 0.30,   // 30% para 61-90 dias
        '91_plus' => 0.50, // 50% para > 90 dias
    ];

    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    /**
     * Calcula e registra a PCLD para um período.
     */
    public function calculateMonthlyPCLD(int $year, int $month, ?string $createdBy = null): Provision
    {
        $period = sprintf('%04d-%02d', $year, $month);

        // Verifica se já existe provisão para o período
        $existing = Provision::pcld()->forPeriod($period)->first();
        if ($existing) {
            Log::info('PCLD já existe para o período', [
                'period' => $period,
                'amount' => $existing->effective_amount,
            ]);
            return $existing;
        }

        // Obtém aging de recebíveis
        $agingReport = $this->reportService->generateAgingReport();
        $aging = $agingReport['aging'];

        // Calcula PCLD por faixa
        $calculationBase = [];
        $totalPCLD = 0;

        foreach (self::PCLD_RATES as $range => $rate) {
            if (isset($aging[$range])) {
                $amount = $aging[$range]['amount'];
                $provision = $amount * $rate;
                
                $calculationBase[$range] = [
                    'receivable_amount' => $amount,
                    'rate' => $rate * 100,
                    'provision_amount' => round($provision, 2),
                ];
                
                $totalPCLD += $provision;
            }
        }

        // Cria a provisão
        $provision = Provision::create([
            'period' => $period,
            'type' => Provision::TYPE_PCLD,
            'calculated_amount' => round($totalPCLD, 2),
            'calculation_base' => $calculationBase,
            'created_by' => $createdBy,
            'notes' => "PCLD calculada automaticamente em " . now()->format('d/m/Y H:i'),
        ]);

        Log::info('PCLD calculada', [
            'period' => $period,
            'amount' => $provision->calculated_amount,
            'base' => $calculationBase,
        ]);

        return $provision;
    }

    /**
     * Recalcula PCLD existente (atualização).
     */
    public function recalculatePCLD(string $period, ?string $updatedBy = null): ?Provision
    {
        $provision = Provision::pcld()->forPeriod($period)->first();

        if (!$provision) {
            return null;
        }

        // Obtém aging atualizado
        $agingReport = $this->reportService->generateAgingReport();
        $aging = $agingReport['aging'];

        // Recalcula
        $calculationBase = [];
        $totalPCLD = 0;

        foreach (self::PCLD_RATES as $range => $rate) {
            if (isset($aging[$range])) {
                $amount = $aging[$range]['amount'];
                $provision_amount = $amount * $rate;
                
                $calculationBase[$range] = [
                    'receivable_amount' => $amount,
                    'rate' => $rate * 100,
                    'provision_amount' => round($provision_amount, 2),
                ];
                
                $totalPCLD += $provision_amount;
            }
        }

        $oldAmount = $provision->calculated_amount;
        $provision->calculated_amount = round($totalPCLD, 2);
        $provision->calculation_base = $calculationBase;
        $provision->notes = ($provision->notes ? $provision->notes . "\n" : '') 
            . "Recalculada em " . now()->format('d/m/Y H:i') . " por {$updatedBy}. Valor anterior: R$ {$oldAmount}";
        $provision->save();

        Log::info('PCLD recalculada', [
            'period' => $period,
            'old_amount' => $oldAmount,
            'new_amount' => $provision->calculated_amount,
        ]);

        return $provision;
    }

    /**
     * Registra baixa contra provisão (quando confirma perda).
     */
    public function writeOff(string $period, float $amount, string $reason, ?string $writtenOffBy = null): Provision
    {
        $provision = Provision::pcld()->forPeriod($period)->first();

        if (!$provision) {
            throw new \Exception("Provisão não encontrada para o período: {$period}");
        }

        if ($amount > $provision->balance) {
            throw new \Exception("Valor de baixa ({$amount}) excede o saldo disponível ({$provision->balance})");
        }

        $provision->use($amount);
        $provision->notes = ($provision->notes ? $provision->notes . "\n" : '')
            . "Baixa de R$ {$amount} em " . now()->format('d/m/Y H:i')
            . " por {$writtenOffBy}. Motivo: {$reason}";
        $provision->save();

        Log::info('Baixa de provisão registrada', [
            'period' => $period,
            'amount' => $amount,
            'reason' => $reason,
            'remaining_balance' => $provision->balance,
        ]);

        return $provision;
    }

    /**
     * Reverte provisão não utilizada no fechamento do período.
     */
    public function reversePCLD(string $period, ?string $reversedBy = null): ?Provision
    {
        $provision = Provision::pcld()->forPeriod($period)->first();

        if (!$provision || $provision->balance <= 0) {
            return null;
        }

        $reversalAmount = $provision->balance;
        
        // Zera o saldo (marca como totalmente utilizado)
        $provision->used_amount = $provision->effective_amount;
        $provision->notes = ($provision->notes ? $provision->notes . "\n" : '')
            . "Reversão de R$ {$reversalAmount} em " . now()->format('d/m/Y H:i')
            . " por {$reversedBy}. Provisão não utilizada no período.";
        $provision->save();

        Log::info('Provisão revertida', [
            'period' => $period,
            'reversal_amount' => $reversalAmount,
        ]);

        return $provision;
    }

    /**
     * Obtém resumo de provisões.
     */
    public function getProvisionSummary(int $monthsBack = 12): array
    {
        $provisions = Provision::pcld()
            ->where('period', '>=', now()->subMonths($monthsBack)->format('Y-m'))
            ->orderBy('period', 'desc')
            ->get();

        $summary = [];
        $totalCalculated = 0;
        $totalUsed = 0;

        foreach ($provisions as $provision) {
            $summary[] = [
                'period' => $provision->period,
                'calculated' => $provision->calculated_amount,
                'adjusted' => $provision->adjusted_amount,
                'effective' => $provision->effective_amount,
                'used' => $provision->used_amount,
                'balance' => $provision->balance,
                'utilization_rate' => $provision->effective_amount > 0
                    ? round(($provision->used_amount / $provision->effective_amount) * 100, 2)
                    : 0,
            ];

            $totalCalculated += $provision->effective_amount;
            $totalUsed += $provision->used_amount;
        }

        return [
            'provisions' => $summary,
            'totals' => [
                'total_provisioned' => round($totalCalculated, 2),
                'total_used' => round($totalUsed, 2),
                'total_balance' => round($totalCalculated - $totalUsed, 2),
                'avg_utilization' => $totalCalculated > 0
                    ? round(($totalUsed / $totalCalculated) * 100, 2)
                    : 0,
            ],
            'rates' => self::PCLD_RATES,
        ];
    }

    /**
     * Obtém análise de efetividade da provisão.
     * Compara provisão calculada vs perdas reais.
     */
    public function getProvisionEffectivenessAnalysis(int $year): array
    {
        $analysis = [];

        for ($month = 1; $month <= 12; $month++) {
            $period = sprintf('%04d-%02d', $year, $month);
            
            $provision = Provision::pcld()->forPeriod($period)->first();
            
            // Perdas reais (faturas canceladas por inadimplência)
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            
            $actualLosses = Invoice::where('status_id', DomainInvoiceStatus::CANCELED)
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->where('notes', 'like', '%inadimpl%')
                ->sum('total_amount');

            $analysis[] = [
                'period' => $period,
                'provisioned' => $provision?->effective_amount ?? 0,
                'actual_losses' => round($actualLosses, 2),
                'variance' => $provision 
                    ? round($provision->effective_amount - $actualLosses, 2)
                    : 0,
                'accuracy' => $provision && $provision->effective_amount > 0
                    ? round(min(100, ($actualLosses / $provision->effective_amount) * 100), 2)
                    : 0,
            ];
        }

        return [
            'year' => $year,
            'monthly' => $analysis,
            'summary' => [
                'total_provisioned' => array_sum(array_column($analysis, 'provisioned')),
                'total_losses' => array_sum(array_column($analysis, 'actual_losses')),
                'avg_accuracy' => count($analysis) > 0
                    ? round(array_sum(array_column($analysis, 'accuracy')) / count($analysis), 2)
                    : 0,
            ],
        ];
    }
}
