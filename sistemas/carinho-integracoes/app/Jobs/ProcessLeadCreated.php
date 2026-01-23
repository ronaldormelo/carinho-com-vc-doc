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
            'phone' => $this->leadData['phone'] ?? 'unknown',
        ]);

        // 1. Verifica se lead ja existe no CRM
        $existingLead = null;
        if (!empty($this->leadData['phone'])) {
            $response = $crm->findLeadByPhone($this->leadData['phone']);
            if ($response['ok'] && !empty($response['body']['data'])) {
                $existingLead = $response['body']['data'][0] ?? null;
            }
        }

        // 2. Cria ou atualiza lead no CRM
        if ($existingLead) {
            $crmResponse = $crm->updateLead($existingLead['id'], [
                'last_contact_at' => now()->toIso8601String(),
                'interaction_count' => ($existingLead['interaction_count'] ?? 0) + 1,
            ]);

            $leadId = $existingLead['id'];

            Log::info('Lead updated in CRM', ['lead_id' => $leadId]);
        } else {
            $crmResponse = $crm->createLead([
                'name' => $this->leadData['name'],
                'phone' => $this->leadData['phone'] ?? null,
                'email' => $this->leadData['email'] ?? null,
                'source' => $this->leadData['source'] ?? 'whatsapp',
                'utm_source' => $this->leadData['utm_source'] ?? null,
                'utm_medium' => $this->leadData['utm_medium'] ?? null,
                'utm_campaign' => $this->leadData['utm_campaign'] ?? null,
                'message' => $this->leadData['message'] ?? null,
            ]);

            if (!$crmResponse['ok']) {
                throw new \Exception('Failed to create lead in CRM');
            }

            $leadId = $crmResponse['body']['id'];

            Log::info('Lead created in CRM', ['lead_id' => $leadId]);
        }

        // 3. Envia mensagem automatica via WhatsApp
        if (!empty($this->leadData['phone'])) {
            SendWhatsAppMessage::dispatch('lead_response', [
                'phone' => $this->leadData['phone'],
                'name' => $this->leadData['name'] ?? 'Cliente',
            ]);

            Log::info('Auto-response scheduled for lead', [
                'phone' => $this->leadData['phone'],
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
            'phone' => $this->leadData['phone'] ?? 'unknown',
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
