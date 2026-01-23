<?php

namespace App\Jobs;

use App\Services\Integrations\Crm\CrmClient;
use App\Services\Integrations\Financeiro\FinanceiroClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para processamento de cliente cadastrado.
 *
 * Fluxo: Cadastro -> Email de boas-vindas + WhatsApp + Setup financeiro
 */
class ProcessClientRegistered implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Cria nova instancia do job.
     */
    public function __construct(
        private array $clientData
    ) {
        $this->onQueue('integrations');
    }

    /**
     * Executa o job.
     */
    public function handle(CrmClient $crm, FinanceiroClient $financeiro): void
    {
        Log::info('Processing client registration', [
            'name' => $this->clientData['name'] ?? 'unknown',
            'client_id' => $this->clientData['id'] ?? 'unknown',
        ]);

        // 1. Envia e-mail de boas-vindas
        if (!empty($this->clientData['email'])) {
            SendEmail::dispatch('welcome', [
                'name' => $this->clientData['name'],
                'email' => $this->clientData['email'],
            ]);

            Log::info('Welcome email scheduled', [
                'email' => $this->clientData['email'],
            ]);
        }

        // 2. Envia mensagem de boas-vindas via WhatsApp
        if (!empty($this->clientData['phone'])) {
            SendWhatsAppMessage::dispatch('welcome', [
                'phone' => $this->clientData['phone'],
                'name' => $this->clientData['name'],
            ]);

            Log::info('Welcome WhatsApp scheduled', [
                'phone' => $this->clientData['phone'],
            ]);
        }

        // 3. Atualiza status do lead no CRM (se veio de lead)
        if (!empty($this->clientData['lead_id'])) {
            $crm->advanceLead($this->clientData['lead_id']);

            // Cria deal se necessario
            $crm->createDeal($this->clientData['lead_id'], [
                'client_id' => $this->clientData['id'],
                'value' => $this->clientData['estimated_value'] ?? 0,
                'service_type' => $this->clientData['service_type'] ?? 'horista',
            ]);

            Log::info('Lead advanced and deal created', [
                'lead_id' => $this->clientData['lead_id'],
            ]);
        }

        // 4. Configura cliente no sistema financeiro
        $financeiroResponse = $financeiro->post('/api/clients', [
            'crm_client_id' => $this->clientData['id'],
            'name' => $this->clientData['name'],
            'email' => $this->clientData['email'] ?? null,
            'phone' => $this->clientData['phone'] ?? null,
            'cpf' => $this->clientData['cpf'] ?? null,
        ]);

        if ($financeiroResponse['ok']) {
            Log::info('Client created in financeiro', [
                'financeiro_id' => $financeiroResponse['body']['id'] ?? 'unknown',
            ]);
        }

        Log::info('Client registration processing completed', [
            'client_id' => $this->clientData['id'],
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Client registration processing failed', [
            'client_id' => $this->clientData['id'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Tags para monitoramento.
     */
    public function tags(): array
    {
        return [
            'client',
            'client_registered',
            'client_id:' . ($this->clientData['id'] ?? 'unknown'),
        ];
    }
}
