<?php

namespace App\Jobs;

use App\Integrations\Operacao\OperacaoClient;
use App\Models\DomainInvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncServiceToInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        protected int $serviceId,
        protected int $clientId,
        protected int $caregiverId
    ) {}

    public function handle(
        OperacaoClient $operacaoClient,
        InvoiceService $invoiceService,
        PricingService $pricingService
    ): void {
        Log::info('Sincronizando serviço para faturamento', [
            'service_id' => $this->serviceId,
            'client_id' => $this->clientId,
        ]);

        // Obtém detalhes do serviço
        $service = $operacaoClient->getService($this->serviceId);

        if (!$service) {
            Log::warning('Serviço não encontrado', ['service_id' => $this->serviceId]);
            return;
        }

        // Busca fatura aberta do cliente ou cria nova
        $invoice = Invoice::where('client_id', $this->clientId)
            ->where('contract_id', $service['contract_id'] ?? 0)
            ->where('status_id', DomainInvoiceStatus::OPEN)
            ->first();

        if (!$invoice) {
            // Cria nova fatura para o serviço
            $invoice = $invoiceService->createInvoice([
                'client_id' => $this->clientId,
                'contract_id' => $service['contract_id'] ?? 0,
                'period_start' => Carbon::parse($service['date']),
                'period_end' => Carbon::parse($service['date']),
                'items' => [],
            ]);
        }

        // Adiciona serviço como item da fatura
        $serviceDate = Carbon::parse($service['date']);
        
        $invoiceService->addItem($invoice, [
            'service_id' => $this->serviceId,
            'service_date' => $serviceDate,
            'description' => $service['description'] ?? 'Serviço de cuidador',
            'qty' => $service['hours'] ?? 0,
            'caregiver_id' => $this->caregiverId,
            'service_type_id' => $service['service_type_id'] ?? null,
            'is_weekend' => $serviceDate->isWeekend(),
            'is_holiday' => $pricingService->isHoliday($serviceDate),
        ]);

        // Marca serviço como faturado no sistema de operação
        $operacaoClient->markServicesAsInvoiced([$this->serviceId], $invoice->id);

        Log::info('Serviço sincronizado', [
            'service_id' => $this->serviceId,
            'invoice_id' => $invoice->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Falha ao sincronizar serviço', [
            'service_id' => $this->serviceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
