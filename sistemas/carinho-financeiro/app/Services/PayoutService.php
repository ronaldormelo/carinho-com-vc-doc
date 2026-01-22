<?php

namespace App\Services;

use App\Events\PayoutCreated;
use App\Events\PayoutProcessed;
use App\Integrations\Stripe\StripeClient;
use App\Models\BankAccount;
use App\Models\DomainOwnerType;
use App\Models\DomainPayoutStatus;
use App\Models\DomainServiceType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payout;
use App\Models\PayoutItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Repasses aos Cuidadores.
 *
 * Responsável por:
 * - Calcular valores devidos aos cuidadores
 * - Aplicar comissões por tipo de serviço
 * - Processar transferências via Stripe Connect
 * - Gerenciar ciclo de repasses
 */
class PayoutService
{
    public function __construct(
        protected StripeClient $stripeClient
    ) {}

    /**
     * Cria repasse para um cuidador.
     */
    public function createPayout(int $caregiverId, Carbon $periodStart, Carbon $periodEnd): Payout
    {
        return DB::transaction(function () use ($caregiverId, $periodStart, $periodEnd) {
            // Busca itens de faturas pagas do período que ainda não foram repassados
            $items = $this->getPendingPayoutItems($caregiverId, $periodStart, $periodEnd);

            if ($items->isEmpty()) {
                throw new \Exception('Não há itens pendentes de repasse para este período');
            }

            // Cria o repasse
            $payout = Payout::create([
                'caregiver_id' => $caregiverId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status_id' => DomainPayoutStatus::OPEN,
                'total_amount' => 0,
            ]);

            // Adiciona itens ao repasse
            foreach ($items as $invoiceItem) {
                $this->addPayoutItem($payout, $invoiceItem);
            }

            // Recalcula totais
            $payout->recalculateTotals();

            Log::info('Repasse criado', [
                'payout_id' => $payout->id,
                'caregiver_id' => $caregiverId,
                'items_count' => $items->count(),
                'total' => $payout->total_amount,
            ]);

            event(new PayoutCreated($payout));

            return $payout->fresh(['items']);
        });
    }

    /**
     * Adiciona item ao repasse.
     */
    protected function addPayoutItem(Payout $payout, InvoiceItem $invoiceItem): PayoutItem
    {
        // Obtém percentual de comissão do cuidador
        $serviceType = $invoiceItem->serviceType;
        $commissionPercent = $serviceType?->getCaregiverCommissionPercent()
            ?? config('financeiro.commission.caregiver_percent', 70);

        // Aplica bonus por avaliação (se aplicável)
        $commissionPercent = $this->applyRatingBonus($payout->caregiver_id, $commissionPercent);

        // Aplica bonus por tempo de casa (se aplicável)
        $commissionPercent = $this->applyTenureBonus($payout->caregiver_id, $commissionPercent);

        // Calcula valor do cuidador
        $caregiverAmount = $invoiceItem->amount * ($commissionPercent / 100);

        return PayoutItem::create([
            'payout_id' => $payout->id,
            'service_id' => $invoiceItem->service_id,
            'invoice_item_id' => $invoiceItem->id,
            'amount' => round($caregiverAmount, 2),
            'commission_percent' => $commissionPercent,
            'service_date' => $invoiceItem->service_date,
            'description' => $invoiceItem->description,
        ]);
    }

    /**
     * Busca itens pendentes de repasse.
     */
    protected function getPendingPayoutItems(int $caregiverId, Carbon $periodStart, Carbon $periodEnd)
    {
        // Busca itens de faturas PAGAS deste cuidador no período
        // que ainda não foram incluídos em nenhum repasse
        return InvoiceItem::whereHas('invoice', function ($q) {
                $q->where('status_id', \App\Models\DomainInvoiceStatus::PAID);
            })
            ->where('caregiver_id', $caregiverId)
            ->whereBetween('service_date', [$periodStart, $periodEnd])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('payout_items')
                    ->whereColumn('payout_items.invoice_item_id', 'invoice_items.id');
            })
            ->with('serviceType')
            ->get();
    }

    /**
     * Processa repasse (transferência).
     */
    public function processPayoutTransfer(Payout $payout): Payout
    {
        if (!$payout->canBeProcessed()) {
            throw new \Exception('Repasse não pode ser processado. Verifique status e valor mínimo.');
        }

        // Obtém conta bancária do cuidador
        $bankAccount = BankAccount::forOwner(DomainOwnerType::CAREGIVER, $payout->caregiver_id)
            ->default()
            ->verified()
            ->first();

        if (!$bankAccount) {
            throw new \Exception('Cuidador não possui conta bancária verificada');
        }

        // Processa transferência via Stripe Connect
        $transferResult = $this->stripeClient->createTransfer([
            'amount' => $payout->net_amount,
            'destination' => $bankAccount->stripe_external_account_id,
            'metadata' => [
                'payout_id' => $payout->id,
                'caregiver_id' => $payout->caregiver_id,
                'period' => "{$payout->period_start->format('Y-m-d')} a {$payout->period_end->format('Y-m-d')}",
            ],
        ]);

        if (!$transferResult['success']) {
            throw new \Exception('Falha na transferência: ' . ($transferResult['error'] ?? 'Erro desconhecido'));
        }

        $payout->markAsPaid($transferResult['transfer_id']);
        $payout->bank_account_id = $bankAccount->id;
        $payout->save();

        Log::info('Repasse processado', [
            'payout_id' => $payout->id,
            'caregiver_id' => $payout->caregiver_id,
            'amount' => $payout->net_amount,
            'transfer_id' => $transferResult['transfer_id'],
        ]);

        event(new PayoutProcessed($payout));

        return $payout;
    }

    /**
     * Processa todos os repasses pendentes.
     */
    public function processAllPendingPayouts(): array
    {
        $processed = [];
        $failed = [];

        $payouts = Payout::readyToProcess()->get();

        foreach ($payouts as $payout) {
            try {
                $this->processPayoutTransfer($payout);
                $processed[] = $payout->id;
            } catch (\Exception $e) {
                Log::error('Falha ao processar repasse', [
                    'payout_id' => $payout->id,
                    'error' => $e->getMessage(),
                ]);
                $failed[] = [
                    'payout_id' => $payout->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'total_processed' => count($processed),
            'total_failed' => count($failed),
        ];
    }

    /**
     * Gera repasses para todos os cuidadores do período.
     */
    public function generatePayoutsForPeriod(Carbon $periodStart, Carbon $periodEnd): array
    {
        // Obtém lista de cuidadores com itens pendentes
        $caregiverIds = InvoiceItem::whereHas('invoice', function ($q) {
                $q->where('status_id', \App\Models\DomainInvoiceStatus::PAID);
            })
            ->whereBetween('service_date', [$periodStart, $periodEnd])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('payout_items')
                    ->whereColumn('payout_items.invoice_item_id', 'invoice_items.id');
            })
            ->distinct()
            ->pluck('caregiver_id');

        $created = [];
        $failed = [];

        foreach ($caregiverIds as $caregiverId) {
            try {
                $payout = $this->createPayout($caregiverId, $periodStart, $periodEnd);
                $created[] = $payout->id;
            } catch (\Exception $e) {
                $failed[] = [
                    'caregiver_id' => $caregiverId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'created' => $created,
            'failed' => $failed,
            'total_created' => count($created),
            'total_failed' => count($failed),
        ];
    }

    /**
     * Obtém resumo de repasses de um cuidador.
     */
    public function getCaregiverPayoutSummary(int $caregiverId): array
    {
        $payouts = Payout::forCaregiver($caregiverId)->get();

        $totalPaid = $payouts->where('status_id', DomainPayoutStatus::PAID)->sum('net_amount');
        $totalPending = $payouts->where('status_id', DomainPayoutStatus::OPEN)->sum('total_amount');

        return [
            'caregiver_id' => $caregiverId,
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
            'payouts_count' => $payouts->count(),
            'paid_count' => $payouts->where('status_id', DomainPayoutStatus::PAID)->count(),
            'pending_count' => $payouts->where('status_id', DomainPayoutStatus::OPEN)->count(),
        ];
    }

    /**
     * Aplica bonus por avaliação.
     */
    protected function applyRatingBonus(int $caregiverId, float $basePercent): float
    {
        $ratingConfig = config('financeiro.commission.rating_bonus');
        
        if (!$ratingConfig) {
            return $basePercent;
        }

        // Aqui seria integrado com o sistema de cuidadores para obter a avaliação
        // Por enquanto, retorna a base
        // $rating = $this->cuidadoresClient->getCaregiverRating($caregiverId);
        // if ($rating >= $ratingConfig['min_rating']) {
        //     return $basePercent + $ratingConfig['bonus_percent'];
        // }

        return $basePercent;
    }

    /**
     * Aplica bonus por tempo de casa.
     */
    protected function applyTenureBonus(int $caregiverId, float $basePercent): float
    {
        $tenureConfig = config('financeiro.commission.tenure_bonus');
        
        if (!$tenureConfig) {
            return $basePercent;
        }

        // Aqui seria integrado com o sistema de cuidadores para obter tempo de casa
        // Por enquanto, retorna a base
        // $months = $this->cuidadoresClient->getCaregiverTenure($caregiverId);
        // foreach ($tenureConfig as $period => $bonus) {
        //     $requiredMonths = (int) str_replace('_months', '', $period);
        //     if ($months >= $requiredMonths) {
        //         return $basePercent + $bonus;
        //     }
        // }

        return $basePercent;
    }

    /**
     * Obtém detalhes de comissão para um tipo de serviço.
     */
    public function getCommissionDetails(int $serviceTypeId): array
    {
        $serviceType = DomainServiceType::find($serviceTypeId);
        
        if (!$serviceType) {
            return [
                'service_type' => 'Não encontrado',
                'caregiver_percent' => config('financeiro.commission.caregiver_percent', 70),
                'company_percent' => config('financeiro.commission.company_percent', 30),
            ];
        }

        $caregiverPercent = $serviceType->getCaregiverCommissionPercent();
        
        return [
            'service_type' => $serviceType->label,
            'service_type_code' => $serviceType->code,
            'caregiver_percent' => $caregiverPercent,
            'company_percent' => 100 - $caregiverPercent,
            'rating_bonus' => config('financeiro.commission.rating_bonus'),
            'tenure_bonus' => config('financeiro.commission.tenure_bonus'),
        ];
    }
}
