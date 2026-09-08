<?php

namespace Database\Seeders;

use App\Models\DomainInvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Seeder;

/**
 * Fatura local de desenvolvimento. Não usar em produção. Sem Stripe.
 */
class DevLocalInvoiceSeeder extends Seeder
{
    public const EXTERNAL_REFERENCE = 'DEV-LOCAL-INVOICE';

    public function run(): void
    {
        $invoice = Invoice::query()->firstOrCreate(
            ['external_reference' => self::EXTERNAL_REFERENCE],
            [
                'client_id' => 1,
                'contract_id' => 1,
                'period_start' => now()->toDateString(),
                'period_end' => now()->addDays(7)->toDateString(),
                'status_id' => DomainInvoiceStatus::OPEN,
                'due_date' => now()->addDays(2)->toDateString(),
                'notes' => 'Fatura local de demonstração (sem Stripe).',
                'total_amount' => 0,
            ]
        );

        if ($invoice->items()->count() === 0) {
            InvoiceItem::query()->create([
                'invoice_id' => $invoice->id,
                'service_date' => now()->toDateString(),
                'description' => 'Plantão horista — demonstração local',
                'qty' => 8,
                'unit_price' => 150,
                'amount' => 1200,
            ]);
            $invoice->recalculateTotal();
        }

        $this->command?->info('Fatura local: GET /api/invoices (token interno) ref ' . self::EXTERNAL_REFERENCE);
    }
}
