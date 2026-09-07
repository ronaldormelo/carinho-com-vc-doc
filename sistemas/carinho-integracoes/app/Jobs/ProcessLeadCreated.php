<?php

namespace App\Jobs;

use App\Services\Integrations\Crm\CrmClient;
use App\Services\Integrations\Marketing\MarketingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para processamento de lead criado.
 *
 * Fluxo: Lead -> Mensagem automatica + Registro no CRM
 */
class ProcessLeadCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Cria nova instancia do job.
     */
    public function __construct(
        private array $leadData
    ) {
        $this->onQueue('integrations');
    }

    /**
     * Executa o job.
     */
    public function handle(CrmClient $crm, MarketingClient $marketing): void
    {
        Log::info('Processing new lead', [
            'name' => $this->leadData['name'] ?? 'unknown',
            'source' => $this->leadData['source'] ?? 'unknown',
        ]);

        $crmResponse = $crm->createLead([
            'name' => $this->leadData['name'] ?? 'Contato WhatsApp',
            'phone' => $this->leadData['phone'] ?? null,
            'email' => $this->leadData['email'] ?? null,
            'source' => $this->leadData['source'] ?? 'whatsapp',
            'city' => $this->leadData['city'] ?? 'nao_informada',
        ]);

        if (!$crmResponse['ok']) {
            throw new \Exception('Failed to create lead in CRM');
        }

        $leadId = $crmResponse['body']['data']['id']
            ?? $crmResponse['body']['id']
            ?? null;

        Log::info('Lead ingested in CRM', ['lead_id' => $leadId]);

        // 3. Envia mensagem automatica via WhatsApp
        if (!empty($this->leadData['phone'])) {
            SendWhatsAppMessage::dispatch('lead_response', [
                'phone' => $this->leadData['phone'],
                'name' => $this->leadData['name'] ?? 'Cliente',
            ]);

            Log::info('Auto-response scheduled for lead', [
                'lead_id' => $leadId,
            ]);
        }

        // 4. Registra atribuicao de campanha se tiver UTM
        if (!empty($this->leadData['utm_campaign'])) {
            $campaign = $marketing->findCampaignByUtm([
                'utm_source' => $this->leadData['utm_source'] ?? null,
                'utm_medium' => $this->leadData['utm_medium'] ?? null,
                'utm_campaign' => $this->leadData['utm_campaign'] ?? null,
            ]);

            if ($campaign['ok'] && !empty($campaign['body']['id'])) {
                $marketing->attributeLeadToCampaign($leadId, $campaign['body']['id']);
            }
        }

        Log::info('Lead processing completed', [
            'lead_id' => $leadId,
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Lead processing failed', [
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Tags para monitoramento.
     */
    public function tags(): array
    {
        return ['lead', 'lead_created'];
    }
}
