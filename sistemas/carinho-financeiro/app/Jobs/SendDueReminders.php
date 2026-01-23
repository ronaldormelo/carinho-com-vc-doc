<?php

namespace App\Jobs;

use App\Integrations\Crm\CrmClient;
use App\Models\Invoice;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDueReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        protected int $daysBeforeDue = 3
    ) {}

    public function handle(
        NotificationService $notificationService,
        CrmClient $crmClient
    ): void {
        Log::info('Iniciando envio de lembretes de vencimento', [
            'days_before' => $this->daysBeforeDue,
        ]);

        $targetDate = now()->addDays($this->daysBeforeDue)->toDateString();
        $sent = 0;

        Invoice::open()
            ->whereDate('due_date', $targetDate)
            ->chunk(50, function ($invoices) use ($notificationService, $crmClient, &$sent) {
                foreach ($invoices as $invoice) {
                    try {
                        $phone = $crmClient->getClientPhone($invoice->client_id);
                        
                        if ($phone) {
                            $success = $notificationService->sendDueReminder(
                                $invoice,
                                $phone,
                                $this->daysBeforeDue
                            );

                            if ($success) {
                                $sent++;
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Erro ao enviar lembrete', [
                            'invoice_id' => $invoice->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('Lembretes enviados', ['count' => $sent]);
    }
}
