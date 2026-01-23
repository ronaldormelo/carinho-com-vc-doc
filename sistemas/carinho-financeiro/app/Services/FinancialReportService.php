<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\DomainFinancialCategory;
use App\Models\DomainInvoiceStatus;
use App\Models\DomainPaymentStatus;
use App\Models\DomainPayoutStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Provision;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Serviço de Relatórios Financeiros.
 *
 * Responsável por gerar relatórios gerenciais consolidados:
 * - DRE (Demonstrativo de Resultado do Exercício)
 * - Aging de Recebíveis
 * - Análise de Margem por Serviço
 * - Indicadores de Performance Financeira
 *
 * Segue práticas tradicionais de contabilidade gerencial,
 * adaptadas para a realidade de empresas de HomeCare.
 */
class FinancialReportService
{
    public function __construct(
        protected SettingService $settingService
    ) {}

    /**
     * Gera DRE (Demonstrativo de Resultado do Exercício).
     *
     * Estrutura simplificada para gestão operacional:
     * - Receita Bruta de Serviços
     * - (-) Deduções (cancelamentos, reembolsos)
     * - (=) Receita Líquida
     * - (-) Custos dos Serviços (repasses aos cuidadores)
     * - (=) Margem Bruta
     * - (-) Despesas Operacionais
     * - (=) Resultado Operacional
     */
    public function generateDRE(Carbon $startDate, Carbon $endDate): array
    {
        // 1. RECEITA BRUTA DE SERVIÇOS
        $grossRevenue = Invoice::where('status_id', DomainInvoiceStatus::PAID)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');

        // Receita de taxas de cancelamento (empresa retém)
        $cancellationFees = Invoice::where('status_id', DomainInvoiceStatus::CANCELED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('cancellation_fee');

        // Receita de juros e multas
        $lateFees = CashTransaction::forPeriod($startDate, $endDate)
            ->where('category_id', DomainFinancialCategory::LATE_FEE)
            ->income()
            ->sum('amount') ?? 0;

        $totalGrossRevenue = $grossRevenue + $cancellationFees + $lateFees;

        // 2. DEDUÇÕES
        // Reembolsos processados
        $refunds = Payment::where('status_id', DomainPaymentStatus::REFUNDED)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('refunded_amount');

        $totalDeductions = $refunds;
        $netRevenue = $totalGrossRevenue - $totalDeductions;

        // 3. CUSTOS DOS SERVIÇOS PRESTADOS
        // Repasses aos cuidadores
        $caregiverPayouts = Payout::where('status_id', DomainPayoutStatus::PAID)
            ->whereBetween('processed_at', [$startDate, $endDate])
            ->sum('total_amount');

        // Taxas de gateway
        $gatewayFees = CashTransaction::forPeriod($startDate, $endDate)
            ->where('category_id', DomainFinancialCategory::GATEWAY_FEE)
            ->expense()
            ->sum('amount') ?? 0;

        // Taxas de transferência
        $transferFees = Payout::where('status_id', DomainPayoutStatus::PAID)
            ->whereBetween('processed_at', [$startDate, $endDate])
            ->sum('transfer_fee');

        $totalCostOfServices = $caregiverPayouts + $gatewayFees + $transferFees;
        $grossMargin = $netRevenue - $totalCostOfServices;
        $grossMarginPercent = $netRevenue > 0 ? ($grossMargin / $netRevenue) * 100 : 0;

        // 4. DESPESAS OPERACIONAIS
        $operationalExpenses = CashTransaction::forPeriod($startDate, $endDate)
            ->where('category_id', DomainFinancialCategory::OPERATIONAL)
            ->expense()
            ->sum('amount') ?? 0;

        $administrativeExpenses = CashTransaction::forPeriod($startDate, $endDate)
            ->where('category_id', DomainFinancialCategory::ADMINISTRATIVE)
            ->expense()
            ->sum('amount') ?? 0;

        // Impostos (ISS, etc.)
        $taxes = CashTransaction::forPeriod($startDate, $endDate)
            ->where('category_id', DomainFinancialCategory::TAX)
            ->expense()
            ->sum('amount') ?? 0;

        $totalOperationalExpenses = $operationalExpenses + $administrativeExpenses + $taxes;

        // 5. RESULTADO OPERACIONAL
        $operationalResult = $grossMargin - $totalOperationalExpenses;
        $operationalMarginPercent = $netRevenue > 0 ? ($operationalResult / $netRevenue) * 100 : 0;

        // 6. PROVISÕES
        $provisions = Provision::pcld()
            ->forPeriod($startDate->format('Y-m'))
            ->value('calculated_amount') ?? 0;

        $netResult = $operationalResult - $provisions;
        $netMarginPercent = $netRevenue > 0 ? ($netResult / $netRevenue) * 100 : 0;

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'description' => $startDate->format('M/Y') === $endDate->format('M/Y')
                    ? $startDate->format('F Y')
                    : $startDate->format('d/m/Y') . ' a ' . $endDate->format('d/m/Y'),
            ],
            'revenue' => [
                'gross_service_revenue' => round($grossRevenue, 2),
                'cancellation_fees' => round($cancellationFees, 2),
                'late_fees' => round($lateFees, 2),
                'total_gross_revenue' => round($totalGrossRevenue, 2),
            ],
            'deductions' => [
                'refunds' => round($refunds, 2),
                'total_deductions' => round($totalDeductions, 2),
            ],
            'net_revenue' => round($netRevenue, 2),
            'cost_of_services' => [
                'caregiver_payouts' => round($caregiverPayouts, 2),
                'gateway_fees' => round($gatewayFees, 2),
                'transfer_fees' => round($transferFees, 2),
                'total_cost' => round($totalCostOfServices, 2),
            ],
            'gross_margin' => [
                'amount' => round($grossMargin, 2),
                'percent' => round($grossMarginPercent, 2),
            ],
            'operational_expenses' => [
                'operational' => round($operationalExpenses, 2),
                'administrative' => round($administrativeExpenses, 2),
                'taxes' => round($taxes, 2),
                'total' => round($totalOperationalExpenses, 2),
            ],
            'operational_result' => [
                'amount' => round($operationalResult, 2),
                'percent' => round($operationalMarginPercent, 2),
            ],
            'provisions' => [
                'pcld' => round($provisions, 2),
            ],
            'net_result' => [
                'amount' => round($netResult, 2),
                'percent' => round($netMarginPercent, 2),
            ],
            'indicators' => [
                'target_margin' => $this->settingService->get('margin_target', 30),
                'minimum_margin' => $this->settingService->get('margin_minimum', 25),
                'margin_alert' => $grossMarginPercent < $this->settingService->get('margin_alert', 20),
            ],
        ];
    }

    /**
     * Gera relatório de Aging de Recebíveis.
     *
     * Classifica os valores a receber por faixas de vencimento,
     * permitindo análise de risco de inadimplência.
     */
    public function generateAgingReport(): array
    {
        $today = now()->startOfDay();

        // Faixas de aging padrão
        $ranges = [
            'current' => ['label' => 'A Vencer', 'min' => null, 'max' => 0],
            '1_30' => ['label' => '1-30 dias', 'min' => 1, 'max' => 30],
            '31_60' => ['label' => '31-60 dias', 'min' => 31, 'max' => 60],
            '61_90' => ['label' => '61-90 dias', 'min' => 61, 'max' => 90],
            '91_plus' => ['label' => '> 90 dias', 'min' => 91, 'max' => null],
        ];

        $aging = [];
        $totalReceivable = 0;

        foreach ($ranges as $key => $range) {
            $query = Invoice::whereIn('status_id', [
                DomainInvoiceStatus::OPEN,
                DomainInvoiceStatus::OVERDUE,
            ]);

            if ($range['min'] === null) {
                // A vencer (due_date >= hoje)
                $query->where('due_date', '>=', $today);
            } elseif ($range['max'] === null) {
                // Mais de X dias (due_date < hoje - min dias)
                $query->where('due_date', '<', $today->copy()->subDays($range['min']));
            } else {
                // Entre X e Y dias
                $query->whereBetween('due_date', [
                    $today->copy()->subDays($range['max']),
                    $today->copy()->subDays($range['min']),
                ]);
            }

            $invoices = $query->get();
            $amount = $invoices->sum('total_amount');
            $count = $invoices->count();

            $aging[$key] = [
                'label' => $range['label'],
                'count' => $count,
                'amount' => round($amount, 2),
                'invoices' => $invoices->take(10)->map(fn($i) => [
                    'id' => $i->id,
                    'client_id' => $i->client_id,
                    'due_date' => $i->due_date->toDateString(),
                    'days_overdue' => max(0, $today->diffInDays($i->due_date, false) * -1),
                    'amount' => $i->total_amount,
                ])->toArray(),
            ];

            $totalReceivable += $amount;
        }

        // Calcula distribuição percentual
        foreach ($aging as $key => &$range) {
            $range['percent'] = $totalReceivable > 0 
                ? round(($range['amount'] / $totalReceivable) * 100, 2)
                : 0;
        }

        // Indicadores de risco
        $overdueAmount = $aging['1_30']['amount'] + $aging['31_60']['amount'] 
            + $aging['61_90']['amount'] + $aging['91_plus']['amount'];
        
        $highRiskAmount = $aging['61_90']['amount'] + $aging['91_plus']['amount'];

        return [
            'date' => $today->toDateString(),
            'aging' => $aging,
            'summary' => [
                'total_receivable' => round($totalReceivable, 2),
                'total_current' => round($aging['current']['amount'], 2),
                'total_overdue' => round($overdueAmount, 2),
                'high_risk' => round($highRiskAmount, 2),
            ],
            'risk_indicators' => [
                'overdue_rate' => $totalReceivable > 0 
                    ? round(($overdueAmount / $totalReceivable) * 100, 2)
                    : 0,
                'high_risk_rate' => $totalReceivable > 0 
                    ? round(($highRiskAmount / $totalReceivable) * 100, 2)
                    : 0,
                'suggested_pcld' => round($this->calculateSuggestedPCLD($aging), 2),
            ],
        ];
    }

    /**
     * Calcula PCLD sugerida com base no aging.
     *
     * Aplica percentuais de provisão por faixa:
     * - 1-30 dias: 3%
     * - 31-60 dias: 10%
     * - 61-90 dias: 30%
     * - > 90 dias: 50%
     */
    protected function calculateSuggestedPCLD(array $aging): float
    {
        $percentByRange = [
            '1_30' => 0.03,
            '31_60' => 0.10,
            '61_90' => 0.30,
            '91_plus' => 0.50,
        ];

        $pcld = 0;
        foreach ($percentByRange as $range => $percent) {
            if (isset($aging[$range])) {
                $pcld += $aging[$range]['amount'] * $percent;
            }
        }

        return $pcld;
    }

    /**
     * Gera análise de margem por tipo de serviço.
     */
    public function generateMarginByServiceType(Carbon $startDate, Carbon $endDate): array
    {
        $serviceTypes = \App\Models\DomainServiceType::all();
        $analysis = [];

        foreach ($serviceTypes as $type) {
            // Receita do tipo de serviço
            $revenue = \App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($startDate, $endDate) {
                    $q->where('status_id', DomainInvoiceStatus::PAID)
                        ->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->where('service_type_id', $type->id)
                ->sum('amount');

            // Custo (repasses) do tipo de serviço
            $cost = \App\Models\PayoutItem::whereHas('payout', function ($q) use ($startDate, $endDate) {
                    $q->where('status_id', DomainPayoutStatus::PAID)
                        ->whereBetween('processed_at', [$startDate, $endDate]);
                })
                ->whereHas('invoiceItem', function ($q) use ($type) {
                    $q->where('service_type_id', $type->id);
                })
                ->sum('amount');

            $margin = $revenue - $cost;
            $marginPercent = $revenue > 0 ? ($margin / $revenue) * 100 : 0;
            $targetMargin = $this->settingService->get('margin_target', 30);

            $analysis[$type->code] = [
                'service_type' => $type->label,
                'revenue' => round($revenue, 2),
                'cost' => round($cost, 2),
                'margin' => round($margin, 2),
                'margin_percent' => round($marginPercent, 2),
                'target_margin' => $targetMargin,
                'variance' => round($marginPercent - $targetMargin, 2),
                'meets_target' => $marginPercent >= $targetMargin,
            ];
        }

        // Totais
        $totalRevenue = array_sum(array_column($analysis, 'revenue'));
        $totalCost = array_sum(array_column($analysis, 'cost'));
        $totalMargin = $totalRevenue - $totalCost;

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'by_service_type' => $analysis,
            'totals' => [
                'revenue' => round($totalRevenue, 2),
                'cost' => round($totalCost, 2),
                'margin' => round($totalMargin, 2),
                'margin_percent' => $totalRevenue > 0 
                    ? round(($totalMargin / $totalRevenue) * 100, 2)
                    : 0,
            ],
        ];
    }

    /**
     * Gera indicadores de performance financeira (KPIs).
     */
    public function getFinancialKPIs(Carbon $startDate, Carbon $endDate): array
    {
        // Ticket Médio
        $avgTicket = Invoice::where('status_id', DomainInvoiceStatus::PAID)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->avg('total_amount') ?? 0;

        // Taxa de Conversão (faturas pagas / faturas emitidas)
        $totalInvoices = Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status_id', [DomainInvoiceStatus::CANCELED])
            ->count();

        $paidInvoices = Invoice::where('status_id', DomainInvoiceStatus::PAID)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $conversionRate = $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0;

        // Taxa de Inadimplência
        $overdueInvoices = Invoice::where('status_id', DomainInvoiceStatus::OVERDUE)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $delinquencyRate = $totalInvoices > 0 ? ($overdueInvoices / $totalInvoices) * 100 : 0;

        // Prazo Médio de Recebimento (PMR)
        $avgDaysToReceive = Payment::where('status_id', DomainPaymentStatus::PAID)
            ->whereHas('invoice', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->selectRaw('AVG(DATEDIFF(paid_at, invoices.created_at)) as avg_days')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->value('avg_days') ?? 0;

        // Receita Recorrente (mensalistas)
        $recurringRevenue = \App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($startDate, $endDate) {
                $q->where('status_id', DomainInvoiceStatus::PAID)
                    ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->where('service_type_id', \App\Models\DomainServiceType::MENSAL)
            ->sum('amount');

        // Comparação com período anterior
        $previousStart = $startDate->copy()->subMonth();
        $previousEnd = $endDate->copy()->subMonth();

        $previousRevenue = Invoice::where('status_id', DomainInvoiceStatus::PAID)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('total_amount');

        $currentRevenue = Invoice::where('status_id', DomainInvoiceStatus::PAID)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');

        $revenueGrowth = $previousRevenue > 0 
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'revenue' => [
                'total' => round($currentRevenue, 2),
                'recurring' => round($recurringRevenue, 2),
                'recurring_percent' => $currentRevenue > 0 
                    ? round(($recurringRevenue / $currentRevenue) * 100, 2)
                    : 0,
                'growth_vs_previous' => round($revenueGrowth, 2),
            ],
            'efficiency' => [
                'avg_ticket' => round($avgTicket, 2),
                'conversion_rate' => round($conversionRate, 2),
                'avg_days_to_receive' => round($avgDaysToReceive, 1),
            ],
            'risk' => [
                'delinquency_rate' => round($delinquencyRate, 2),
                'overdue_invoices' => $overdueInvoices,
            ],
            'targets' => [
                'target_margin' => $this->settingService->get('margin_target', 30),
                'max_delinquency' => 10, // Meta: máximo 10% de inadimplência
            ],
        ];
    }
}
