<?php

namespace App\Jobs;

use App\Integrations\Crm\CrmClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para sincronizar eventos com o CRM.
 */
class SyncWithCrm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private string $event,
        private array $data
    ) {}

    public function handle(CrmClient $crm): void
    {
        try {
            $result = match ($this->event) {
                'contract_created' => $crm->notifyContractCreated($this->data),
                'contract_signed' => $crm->notifyContractSigned($this->data),
                'consent_granted' => $crm->notifyConsentGranted($this->data),
                'consent_revoked' => $crm->notifyConsentRevoked($this->data),
                'data_request_created' => $crm->notifyDataRequest($this->data),
                default => ['ok' => false, 'error' => 'Evento desconhecido'],
            };

            if (!$result['ok']) {
                throw new \Exception($result['error'] ?? 'Falha na sincronizacao');
            }

            Log::info('CRM sync completed', [
                'event' => $this->event,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to sync with CRM', [
                'event' => $this->event,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
