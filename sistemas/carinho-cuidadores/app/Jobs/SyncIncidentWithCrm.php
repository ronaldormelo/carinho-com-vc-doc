<?php

namespace App\Jobs;

use App\Models\CaregiverIncident;
use App\Integrations\Crm\CrmClient;
use App\Integrations\Integracoes\IntegracoesClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncIncidentWithCrm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 60;

    public function __construct(
        private CaregiverIncident $incident
    ) {
        $this->onQueue('integrations');
    }

    public function handle(CrmClient $crmClient, IntegracoesClient $integracoesClient): void
    {
        Log::info('Sincronizando incidente com CRM', [
            'incident_id' => $this->incident->id,
            'caregiver_id' => $this->incident->caregiver_id,
        ]);

        // Sincroniza com CRM
        $crmResult = $crmClient->registerIncident([
            'caregiver_id' => $this->incident->caregiver_id,
            'service_id' => $this->incident->service_id,
            'incident_type' => $this->incident->incident_type,
            'notes' => $this->incident->notes,
            'occurred_at' => $this->incident->occurred_at->toIso8601String(),
        ]);

        if (!$crmResult['ok']) {
            Log::warning('Falha ao registrar incidente no CRM', [
                'incident_id' => $this->incident->id,
                'response' => $crmResult,
            ]);
        }

        // Publica evento no hub de integracoes
        $integracoesClient->incidentRegistered(
            $this->incident->caregiver_id,
            $this->incident->service_id,
            $this->incident->incident_type
        );

        Log::info('Incidente sincronizado com CRM', [
            'incident_id' => $this->incident->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job de sincronizacao de incidente falhou', [
            'incident_id' => $this->incident->id,
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return [
            'incident-sync',
            'incident:' . $this->incident->id,
            'caregiver:' . $this->incident->caregiver_id,
        ];
    }
}
