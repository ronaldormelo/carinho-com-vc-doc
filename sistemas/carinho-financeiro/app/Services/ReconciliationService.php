<?php

namespace App\Services;

use App\Models\DomainInvoiceStatus;
use App\Models\DomainPaymentStatus;
use App\Models\DomainPayoutStatus;
use App\Models\DomainReconciliationStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Reconciliation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Conciliação Bancária.
 *
 * Responsável por:
 * - Conciliar receitas x despesas
 * - Verificar discrepâncias
 * - Gerar relatórios de fechamento
 * - Monitorar fluxo de caixa
 */
class ReconciliationService
{
    /**
     * Cria ou obtém conciliação de um período.
     */
    public function getOrCreateReconciliation(string $period): Reconciliation
    {
        $reconciliation = Reconciliation::forPeriod($period)->first();

        if ($reconciliation) {
            return $reconciliation;
        }

        return Reconciliation::create([
            'period' => $period,
            'status_id' => DomainReconciliationStatus::OPEN,
            'started_at' => now(),
        ]);
    }

    /**
     * Processa conciliação de um mês.
     */
    public function processMonthlyReconciliation(int $year, int $month): Reconciliation
    {
        $period = sprintf('%04d-%02d', $year, $month);
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $reconciliation = $this->getOrCreateReconciliation($period);

        if ($reconciliation->isClosed()) {
            throw new \Exception('Esta conciliação já foi fechada');
        }

        return DB::transaction(function () use ($reconciliation, $startDate, $endDate) {
            // Calcula totais
            $totals = $this->calculatePeriodTotals($startDate, $endDate);

            $reconciliation->fill([
                'total_invoiced' => $totals['invoiced'],
                'total_received' => $totals['received'],
                'total_payouts' => $totals['payouts'],
                'total_fees' => $totals['fees'],
            ]);

            $reconciliation->calculateBalance();

            Log::info('Conciliação processada', [
                'period' => $reconciliation->period,
                'balance' => $reconciliation->balance,
                'discrepancy' => $reconciliation->discrepancy_amount,
            ]);

            return $reconciliation;
        });
    }

    /**
     * Calcula totais do período.
     */
    protected function calculatePeriodTotals(Carbon $startDate, Carbon $endDate): array
    {
        // Total faturado (faturas criadas no período)
        $invoiced = Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status_id', [DomainInvoiceStatus::CANCELED])
            ->sum('total_amount');

        // Total recebido (pagamentos confirmados no período)
        $received = Payment::where('status_id', DomainPaymentStatus::PAID)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('amount');

        // Total de repasses (repasses pagos no período)
        $payouts = Payout::where('status_id', DomainPayoutStatus::PAID)
            ->whereBetween('processed_at', [$startDate, $endDate])
            ->sum('net_amount');

        // Taxas (fees de transferência)
        $fees = Payout::where('status_id', DomainPayoutStatus::PAID)
            ->whereBetween('processed_at', [$startDate, $endDate])
            ->sum('transfer_fee');

        return [
            'invoiced' => $invoiced,
            'received' => $received,
            'payouts' => $payouts,
            'fees' => $fees,
        ];
    }

    /**
     * Fecha uma conciliação.
     */
    public function closeReconciliation(Reconciliation $reconciliation, ?string $closedBy = null): Reconciliation
    {
        if ($reconciliation->isClosed()) {
            throw new \Exception('Conciliação já está fechada');
        }

        // Verifica se há discrepância significativa
        if (abs($reconciliation->discrepancy_amount ?? 0) > 0.01) {
            Log::warning('Fechando conciliação com discrepância', [
                'period' => $reconciliation->period,
                'discrepancy' => $reconciliation->discrepancy_amount,
            ]);
        }

        $reconciliation->close($closedBy);

        Log::info('Conciliação fechada', [
            'period' => $reconciliation->period,
            'closed_by' => $closedBy,
        ]);

        return $reconciliation;
    }

    /**
     * Gera relatório de fluxo de caixa.
     */
    public function getCashFlowReport(Carbon $startDate, Carbon $endDate): array
    {
        // Entradas (pagamentos recebidos)
        $entries = Payment::where('status_id', DomainPaymentStatus::PAID)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(paid_at) as date'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Saídas (repasses processados)
        $exits = Payout::where('status_id', DomainPayoutStatus::PAID)
            ->whereBetween('processed_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(processed_at) as date'),
                DB::raw('SUM(net_amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Totais
        $totalEntries = $entries->sum('total');
        $totalExits = $exits->sum('total');

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'entries' => [
                'daily' => $entries->toArray(),
                'total' => $totalEntries,
                'count' => $entries->sum('count'),
            ],
            'exits' => [
                'daily' => $exits->toArray(),
                'total' => $totalExits,
                'count' => $exits->sum('count'),
            ],
            'balance' => $totalEntries - $totalExits,
            'margin' => $totalEntries > 0 
                ? round((($totalEntries - $totalExits) / $totalEntries) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Obtém indicadores financeiros.
     */
    public function getFinancialIndicators(Carbon $startDate, Carbon $endDate): array
    {
        // Ticket médio
        $avgTicket = Invoice::where('status_id', DomainInvoiceStatus::PAID)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->avg('total_amount');

        // Taxa de inadimplência
        $totalInvoices = Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status_id', [DomainInvoiceStatus::CANCELED])
            ->count();

        $overdueInvoices = Invoice::where('status_id', DomainInvoiceStatus::OVERDUE)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $delinquencyRate = $totalInvoices > 0 
            ? ($overdueInvoices / $totalInvoices) * 100 
            : 0;

        // Receita recorrente (mensalistas)
        $recurringRevenue = InvoiceItem::whereHas('invoice', function ($q) use ($startDate, $endDate) {
                $q->where('status_id', DomainInvoiceStatus::PAID)
                    ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->where('service_type_id', \App\Models\DomainServiceType::MENSAL)
            ->sum('amount');

        // Margem média
        $totalRevenue = Invoice::where('status_id', DomainInvoiceStatus::PAID)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');

        $totalPayouts = Payout::where('status_id', DomainPayoutStatus::PAID)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');

        $avgMargin = $totalRevenue > 0 
            ? (($totalRevenue - $totalPayouts) / $totalRevenue) * 100 
            : 0;

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'avg_ticket' => round($avgTicket ?? 0, 2),
            'delinquency_rate' => round($delinquencyRate, 2),
            'recurring_revenue' => round($recurringRevenue, 2),
            'total_revenue' => round($totalRevenue, 2),
            'total_payouts' => round($totalPayouts, 2),
            'gross_margin' => round($avgMargin, 2),
            'alerts' => $this->generateAlerts($delinquencyRate, $avgMargin),
        ];
    }

    /**
     * Gera alertas baseados nos indicadores.
     */
    protected function generateAlerts(float $delinquencyRate, float $margin): array
    {
        $alerts = [];
        $marginAlert = config('financeiro.margin.alert_threshold', 20);

        if ($delinquencyRate > 10) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Taxa de inadimplência alta: {$delinquencyRate}%",
            ];
        }

        if ($margin < $marginAlert) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Margem abaixo do limite: {$margin}%",
            ];
        }

        return $alerts;
    }

    /**
     * Lista faturas não conciliadas.
     */
    public function getUnreconciledInvoices(): array
    {
        return Invoice::where('status_id', DomainInvoiceStatus::PAID)
            ->whereDoesntHave('payments', function ($q) {
                $q->where('status_id', DomainPaymentStatus::PAID);
            })
            ->get()
            ->toArray();
    }

    /**
     * Lista pagamentos sem fatura correspondente.
     */
    public function getOrphanPayments(): array
    {
        return Payment::where('status_id', DomainPaymentStatus::PAID)
            ->whereDoesntHave('invoice')
            ->get()
            ->toArray();
    }
}
