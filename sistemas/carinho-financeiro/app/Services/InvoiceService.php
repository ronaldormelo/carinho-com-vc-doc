<?php

namespace App\Services;

use App\Events\InvoiceCanceled;
use App\Events\InvoiceCreated;
use App\Events\InvoiceOverdue;
use App\Events\InvoicePaid;
use App\Models\DomainInvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Faturas.
 *
 * Responsável por:
 * - Criar e gerenciar faturas
 * - Calcular valores e taxas
 * - Aplicar políticas de cancelamento
 * - Gerenciar ciclo de vida da fatura
 */
class InvoiceService
{
    public function __construct(
        protected PricingService $pricingService,
        protected CancellationService $cancellationService
    ) {}

    /**
     * Cria uma nova fatura.
     */
    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            // Calcula data de vencimento (sempre adiantado)
            $advanceHours = config('financeiro.payment.advance_hours', 24);
            $serviceDate = isset($data['service_date']) 
                ? Carbon::parse($data['service_date']) 
                : now()->addDays(3);
            
            $dueDate = $serviceDate->copy()->subHours($advanceHours);
            
            // Se a data de vencimento já passou, define para agora + 24h
            if ($dueDate->isPast()) {
                $dueDate = now()->addHours(24);
            }

            $invoice = Invoice::create([
                'client_id' => $data['client_id'],
                'contract_id' => $data['contract_id'],
                'period_start' => $data['period_start'] ?? null,
                'period_end' => $data['period_end'] ?? null,
                'status_id' => DomainInvoiceStatus::OPEN,
                'due_date' => $dueDate,
                'notes' => $data['notes'] ?? null,
                'external_reference' => $data['external_reference'] ?? null,
                'total_amount' => 0,
            ]);

            // Adiciona itens se fornecidos
            if (!empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $this->addItem($invoice, $itemData);
                }
            }

            // Recalcula total
            $invoice->recalculateTotal();

            Log::info('Fatura criada', [
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'total' => $invoice->total_amount,
            ]);

            event(new InvoiceCreated($invoice));

            return $invoice->fresh(['items', 'status']);
        });
    }

    /**
     * Adiciona item à fatura.
     */
    public function addItem(Invoice $invoice, array $data): InvoiceItem
    {
        // Calcula preço se não fornecido
        if (!isset($data['unit_price']) && isset($data['service_type_id'])) {
            $pricing = $this->pricingService->calculatePrice([
                'service_type_id' => $data['service_type_id'],
                'qty' => $data['qty'] ?? 1,
                'is_weekend' => $data['is_weekend'] ?? false,
                'is_holiday' => $data['is_holiday'] ?? false,
            ]);
            $data['unit_price'] = $pricing['unit_price'];
        }

        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'service_id' => $data['service_id'] ?? null,
            'service_date' => $data['service_date'] ?? now(),
            'description' => $data['description'],
            'qty' => $data['qty'] ?? 1,
            'unit_price' => $data['unit_price'],
            'caregiver_id' => $data['caregiver_id'] ?? null,
            'service_type_id' => $data['service_type_id'] ?? null,
        ]);

        // Recalcula total da fatura
        $invoice->recalculateTotal();

        return $item;
    }

    /**
     * Remove item da fatura.
     */
    public function removeItem(Invoice $invoice, int $itemId): bool
    {
        if (!$invoice->isOpen()) {
            throw new \Exception('Não é possível remover itens de faturas já processadas');
        }

        $item = $invoice->items()->findOrFail($itemId);
        $item->delete();

        $invoice->recalculateTotal();

        return true;
    }

    /**
     * Aplica desconto à fatura.
     */
    public function applyDiscount(Invoice $invoice, float $amount, ?string $reason = null): Invoice
    {
        if (!$invoice->isOpen()) {
            throw new \Exception('Não é possível aplicar desconto em faturas já processadas');
        }

        $invoice->discount_amount = $amount;
        
        if ($reason) {
            $invoice->notes = ($invoice->notes ? $invoice->notes . "\n" : '') . "Desconto: {$reason}";
        }

        $invoice->recalculateTotal();

        Log::info('Desconto aplicado', [
            'invoice_id' => $invoice->id,
            'discount' => $amount,
            'reason' => $reason,
        ]);

        return $invoice;
    }

    /**
     * Marca fatura como paga.
     */
    public function markAsPaid(Invoice $invoice): Invoice
    {
        if (!$invoice->canBePaid()) {
            throw new \Exception('Fatura não pode ser marcada como paga');
        }

        $invoice->markAsPaid();

        Log::info('Fatura marcada como paga', [
            'invoice_id' => $invoice->id,
        ]);

        event(new InvoicePaid($invoice));

        return $invoice;
    }

    /**
     * Cancela uma fatura.
     */
    public function cancelInvoice(Invoice $invoice, string $reason, ?Carbon $serviceDate = null): array
    {
        if (!$invoice->canBeCanceled()) {
            throw new \Exception('Fatura não pode ser cancelada');
        }

        $result = $this->cancellationService->processInvoiceCancellation(
            $invoice,
            $reason,
            $serviceDate
        );

        $invoice->markAsCanceled();
        $invoice->notes = ($invoice->notes ? $invoice->notes . "\n" : '') 
            . "Cancelada: {$reason}";
        $invoice->cancellation_fee = $result['cancellation_fee'] ?? 0;
        $invoice->save();

        Log::info('Fatura cancelada', [
            'invoice_id' => $invoice->id,
            'reason' => $reason,
            'refund_amount' => $result['refund_amount'] ?? 0,
        ]);

        event(new InvoiceCanceled($invoice, $result));

        return $result;
    }

    /**
     * Processa faturas vencidas.
     */
    public function processOverdueInvoices(): int
    {
        $count = 0;

        Invoice::open()
            ->where('due_date', '<', now())
            ->chunk(100, function ($invoices) use (&$count) {
                foreach ($invoices as $invoice) {
                    $invoice->markAsOverdue();
                    event(new InvoiceOverdue($invoice));
                    $count++;

                    Log::info('Fatura marcada como vencida', [
                        'invoice_id' => $invoice->id,
                    ]);
                }
            });

        return $count;
    }

    /**
     * Obtém resumo financeiro de um cliente.
     */
    public function getClientSummary(int $clientId): array
    {
        $invoices = Invoice::forClient($clientId)->get();

        $totalInvoiced = $invoices->sum('total_amount');
        $totalPaid = $invoices->where('status_id', DomainInvoiceStatus::PAID)->sum('total_amount');
        $totalOpen = $invoices->where('status_id', DomainInvoiceStatus::OPEN)->sum('total_amount');
        $totalOverdue = $invoices->where('status_id', DomainInvoiceStatus::OVERDUE)->sum('total_amount');

        return [
            'client_id' => $clientId,
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'total_open' => $totalOpen,
            'total_overdue' => $totalOverdue,
            'invoices_count' => $invoices->count(),
            'paid_count' => $invoices->where('status_id', DomainInvoiceStatus::PAID)->count(),
            'open_count' => $invoices->where('status_id', DomainInvoiceStatus::OPEN)->count(),
            'overdue_count' => $invoices->where('status_id', DomainInvoiceStatus::OVERDUE)->count(),
            'has_overdue' => $totalOverdue > 0,
        ];
    }

    /**
     * Gera faturas para serviços de um período.
     */
    public function generateInvoicesForPeriod(int $clientId, int $contractId, Carbon $periodStart, Carbon $periodEnd, array $services): Invoice
    {
        $items = [];

        foreach ($services as $service) {
            $items[] = [
                'service_id' => $service['id'],
                'service_date' => $service['date'],
                'description' => $service['description'] ?? 'Serviço de cuidador',
                'qty' => $service['hours'],
                'caregiver_id' => $service['caregiver_id'],
                'service_type_id' => $service['service_type_id'],
                'is_weekend' => Carbon::parse($service['date'])->isWeekend(),
                'is_holiday' => $this->pricingService->isHoliday(Carbon::parse($service['date'])),
            ];
        }

        return $this->createInvoice([
            'client_id' => $clientId,
            'contract_id' => $contractId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'items' => $items,
        ]);
    }
}
