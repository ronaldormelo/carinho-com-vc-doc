<?php

namespace App\Jobs;

use App\Integrations\Crm\CrmClient;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOverdueInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(
        InvoiceService $invoiceService,
        NotificationService $notificationService,
        CrmClient $crmClient
    ): void {
        Log::info('Iniciando processamento de faturas vencidas');

        $count = $invoiceService->processOverdueInvoices();

        Log::info('Faturas vencidas processadas', ['count' => $count]);

        // Para cada fatura recém-marcada como vencida, envia notificação
        Invoice::overdue()
            ->whereDate('due_date', '=', now()->subDay())
            ->chunk(50, function ($invoices) use ($notificationService, $crmClient) {
                foreach ($invoices as $invoice) {
                    try {
                        $phone = $crmClient->getClientPhone($invoice->client_id);
                        
                        if ($phone) {
                            $notificationService->notifyInvoiceOverdue($invoice, $phone);
                        }

                        // Notifica CRM
                        $crmClient->notifyInvoiceOverdue(
                            $invoice->contract_id,
                            $invoice->id,
                            $invoice->total_with_fees
                        );
                    } catch (\Exception $e) {
                        Log::error('Erro ao notificar fatura vencida', [
                            'invoice_id' => $invoice->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }
}
