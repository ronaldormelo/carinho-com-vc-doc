<?php

namespace App\Jobs;

use App\Integrations\Internal\CrmClient;
use App\Integrations\Internal\IntegracoesClient;
use App\Integrations\WhatsApp\ZApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para notificar sobre novo lead criado.
 */
class NotifyLeadCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero de tentativas.
     */
    public int $tries = 3;

    /**
     * Tempo limite em segundos.
     */
    public int $timeout = 60;

    /**
     * Tempo de backoff entre tentativas.
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        private array $leadData,
        private array $sourceData = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        CrmClient $crm,
        IntegracoesClient $integracoes,
        ZApiClient $zapi
    ): void {
        try {
            // Envia para o CRM
            $crmResult = $crm->sendLead([
                'name' => $this->leadData['name'] ?? null,
                'email' => $this->leadData['email'] ?? null,
                'phone' => $this->leadData['phone'] ?? null,
                'source' => $this->sourceData,
            ]);

            Log::info('Lead sent to CRM', [
                'lead' => $this->leadData,
                'crm_result' => $crmResult,
            ]);

            // Dispara evento no hub de integracoes
            $integracoes->dispatchLeadCreated($this->leadData, $this->sourceData);

            // Envia mensagem de boas-vindas via WhatsApp (se tiver telefone)
            if (!empty($this->leadData['phone'])) {
                $welcomeMessage = config('branding.messages.welcome', 
                    'Obrigado por entrar em contato! Em breve retornaremos.');

                $zapi->sendTextMessage($this->leadData['phone'], $welcomeMessage);

                Log::info('Welcome message sent', [
                    'phone' => $this->leadData['phone'],
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('NotifyLeadCreated failed', [
                'lead' => $this->leadData,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('NotifyLeadCreated job failed permanently', [
            'lead' => $this->leadData,
            'error' => $exception->getMessage(),
        ]);
    }
}
