<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\DomainFinancialCategory;
use App\Models\DomainTransactionType;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Fluxo de Caixa.
 *
 * Responsável por:
 * - Registrar transações financeiras de entrada e saída
 * - Gerar relatórios de fluxo de caixa por período
 * - Calcular saldos e projeções
 * - Categorizar movimentações por tipo e categoria
 *
 * Este serviço segue práticas tradicionais de controle financeiro,
 * permitindo rastreabilidade completa de todas as movimentações.
 */
class CashFlowService
{
    public function __construct(
        protected SettingService $settingService
    ) {}

    /**
     * Registra uma transação de entrada (recebimento).
     */
    public function registerIncome(array $data): CashTransaction
    {
        return $this->registerTransaction(array_merge($data, [
            'direction' => CashTransaction::DIRECTION_IN,
        ]));
    }

    /**
     * Registra uma transação de saída (pagamento).
     */
    public function registerExpense(array $data): CashTransaction
    {
        return $this->registerTransaction(array_merge($data, [
            'direction' => CashTransaction::DIRECTION_OUT,
        ]));
    }

    /**
     * Registra uma transação financeira.
     */
    public function registerTransaction(array $data): CashTransaction
    {
        $transaction = CashTransaction::create([
            'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
            'competence_date' => $data['competence_date'] ?? $data['transaction_date'] ?? now()->toDateString(),
            'type_id' => $data['type_id'],
            'category_id' => $data['category_id'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'direction' => $data['direction'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'external_reference' => $data['external_reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);

        Log::info('Transação financeira registrada', [
            'transaction_id' => $transaction->id,
            'type' => $data['direction'],
            'amount' => $data['amount'],
            'category_id' => $data['category_id'],
        ]);

        return $transaction;
    }

    /**
     * Registra recebimento de pagamento de fatura.
     */
    public function registerPaymentReceived(Payment $payment): CashTransaction
    {
        return $this->registerIncome([
            'transaction_date' => $payment->paid_at?->toDateString() ?? now()->toDateString(),
            'competence_date' => $payment->invoice->period_start?->toDateString(),
            'type_id' => DomainTransactionType::RECEIPT,
            'category_id' => DomainFinancialCategory::SERVICE_REVENUE,
            'description' => "Pagamento fatura #{$payment->invoice_id}",
            'amount' => $payment->amount,
            'reference_type' => 'payment',
            'reference_id' => $payment->id,
            'external_reference' => $payment->stripe_payment_intent_id,
        ]);
    }

    /**
     * Registra repasse ao cuidador.
     */
    public function registerPayoutProcessed(Payout $payout): CashTransaction
    {
        return $this->registerExpense([
            'transaction_date' => $payout->processed_at?->toDateString() ?? now()->toDateString(),
            'competence_date' => $payout->period_end?->toDateString(),
            'type_id' => DomainTransactionType::PAYMENT,
            'category_id' => DomainFinancialCategory::CAREGIVER_PAYOUT,
            'description' => "Repasse cuidador #{$payout->caregiver_id} - período {$payout->period_start?->format('d/m')} a {$payout->period_end?->format('d/m')}",
            'amount' => $payout->net_amount,
            'reference_type' => 'payout',
            'reference_id' => $payout->id,
            'external_reference' => $payout->stripe_transfer_id,
        ]);
    }

    /**
     * Registra taxa do gateway de pagamento.
     */
    public function registerGatewayFee(Payment $payment, float $feeAmount): CashTransaction
    {
        return $this->registerExpense([
            'transaction_date' => $payment->paid_at?->toDateString() ?? now()->toDateString(),
            'type_id' => DomainTransactionType::FEE,
            'category_id' => DomainFinancialCategory::GATEWAY_FEE,
            'description' => "Taxa Stripe - Pagamento #{$payment->id}",
            'amount' => $feeAmount,
            'reference_type' => 'payment',
            'reference_id' => $payment->id,
        ]);
    }

    /**
     * Registra reembolso processado.
     */
    public function registerRefund(Payment $payment, float $refundAmount): CashTransaction
    {
        return $this->registerExpense([
            'transaction_date' => now()->toDateString(),
            'type_id' => DomainTransactionType::REFUND,
            'category_id' => DomainFinancialCategory::REFUND_EXPENSE,
            'description' => "Reembolso pagamento #{$payment->id}",
            'amount' => $refundAmount,
            'reference_type' => 'payment',
            'reference_id' => $payment->id,
        ]);
    }

    /**
     * Obtém saldo do período.
     */
    public function getPeriodBalance(Carbon $startDate, Carbon $endDate): array
    {
        $transactions = CashTransaction::forPeriod($startDate, $endDate)->get();

        $totalIn = $transactions->where('direction', CashTransaction::DIRECTION_IN)->sum('amount');
        $totalOut = $transactions->where('direction', CashTransaction::DIRECTION_OUT)->sum('amount');

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'total_income' => round($totalIn, 2),
            'total_expense' => round($totalOut, 2),
            'balance' => round($totalIn - $totalOut, 2),
            'transactions_count' => $transactions->count(),
        ];
    }

    /**
     * Obtém fluxo de caixa diário.
     */
    public function getDailyCashFlow(Carbon $startDate, Carbon $endDate): array
    {
        $transactions = CashTransaction::forPeriod($startDate, $endDate)
            ->select(
                'transaction_date',
                'direction',
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('transaction_date', 'direction')
            ->orderBy('transaction_date')
            ->get();

        // Agrupa por data
        $grouped = $transactions->groupBy('transaction_date');
        
        $dailyFlow = [];
        $runningBalance = 0;

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->toDateString();
            $dayData = $grouped->get($dateStr, collect());

            $income = $dayData->where('direction', CashTransaction::DIRECTION_IN)->sum('total');
            $expense = $dayData->where('direction', CashTransaction::DIRECTION_OUT)->sum('total');
            $dayBalance = $income - $expense;
            $runningBalance += $dayBalance;

            $dailyFlow[] = [
                'date' => $dateStr,
                'income' => round($income, 2),
                'expense' => round($expense, 2),
                'day_balance' => round($dayBalance, 2),
                'running_balance' => round($runningBalance, 2),
            ];

            $currentDate->addDay();
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'daily' => $dailyFlow,
            'summary' => [
                'total_income' => round(collect($dailyFlow)->sum('income'), 2),
                'total_expense' => round(collect($dailyFlow)->sum('expense'), 2),
                'final_balance' => round($runningBalance, 2),
            ],
        ];
    }

    /**
     * Obtém fluxo de caixa por categoria.
     */
    public function getCashFlowByCategory(Carbon $startDate, Carbon $endDate): array
    {
        $transactions = CashTransaction::forPeriod($startDate, $endDate)
            ->select(
                'category_id',
                'direction',
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('category_id', 'direction')
            ->with('category')
            ->get();

        $revenues = [];
        $expenses = [];

        foreach ($transactions as $tx) {
            $item = [
                'category_id' => $tx->category_id,
                'category_code' => $tx->category->code ?? 'unknown',
                'category_label' => $tx->category->label ?? 'Desconhecido',
                'total' => round($tx->total, 2),
                'count' => $tx->count,
            ];

            if ($tx->direction === CashTransaction::DIRECTION_IN) {
                $revenues[] = $item;
            } else {
                $expenses[] = $item;
            }
        }

        $totalRevenue = array_sum(array_column($revenues, 'total'));
        $totalExpense = array_sum(array_column($expenses, 'total'));

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'revenues' => $revenues,
            'expenses' => $expenses,
            'summary' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_expense' => round($totalExpense, 2),
                'gross_margin' => round($totalRevenue - $totalExpense, 2),
                'margin_percent' => $totalRevenue > 0 
                    ? round((($totalRevenue - $totalExpense) / $totalRevenue) * 100, 2)
                    : 0,
            ],
        ];
    }

    /**
     * Obtém transações recentes.
     */
    public function getRecentTransactions(int $limit = 50): array
    {
        return CashTransaction::with(['type', 'category'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'date' => $tx->transaction_date->toDateString(),
                    'type' => $tx->type->label ?? 'Desconhecido',
                    'category' => $tx->category->label ?? 'Desconhecido',
                    'description' => $tx->description,
                    'amount' => $tx->amount,
                    'direction' => $tx->direction,
                    'signed_amount' => $tx->signed_amount,
                ];
            })
            ->toArray();
    }

    /**
     * Obtém previsão de fluxo de caixa.
     */
    public function getCashFlowForecast(int $days = 30): array
    {
        $startDate = now();
        $endDate = now()->addDays($days);

        // Recebimentos esperados (faturas em aberto)
        $expectedIncome = Invoice::whereIn('status_id', [
                \App\Models\DomainInvoiceStatus::OPEN,
                \App\Models\DomainInvoiceStatus::OVERDUE,
            ])
            ->whereBetween('due_date', [$startDate, $endDate])
            ->select(
                'due_date',
                DB::raw('SUM(total_amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('due_date')
            ->orderBy('due_date')
            ->get()
            ->map(fn ($i) => [
                'date' => $i->due_date->toDateString(),
                'type' => 'expected_income',
                'amount' => round($i->total, 2),
                'count' => $i->count,
            ])
            ->toArray();

        // Repasses previstos (baseado na média histórica)
        $avgWeeklyPayout = Payout::where('status_id', \App\Models\DomainPayoutStatus::PAID)
            ->where('processed_at', '>=', now()->subMonths(3))
            ->avg('net_amount') ?? 0;

        $expectedPayouts = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            // Sexta-feira (dia 5)
            if ($currentDate->dayOfWeek === 5) {
                $expectedPayouts[] = [
                    'date' => $currentDate->toDateString(),
                    'type' => 'expected_payout',
                    'amount' => round($avgWeeklyPayout * 10, 2), // Estimativa para ~10 cuidadores
                    'is_estimate' => true,
                ];
            }
            $currentDate->addDay();
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'expected_income' => $expectedIncome,
            'expected_payouts' => $expectedPayouts,
            'summary' => [
                'total_expected_income' => array_sum(array_column($expectedIncome, 'amount')),
                'total_expected_payout' => array_sum(array_column($expectedPayouts, 'amount')),
            ],
            'note' => 'Valores de repasse são estimativas baseadas na média histórica.',
        ];
    }
}
