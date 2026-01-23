<?php

namespace App\Jobs;

use App\Models\Caregiver;
use App\Integrations\Crm\CrmClient;
use App\Integrations\Integracoes\IntegracoesClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCaregiverWithCrm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    public function __construct(
        private Caregiver $caregiver,
        private string $action
    ) {
        $this->onQueue('integrations');
    }

    public function handle(CrmClient $crmClient, IntegracoesClient $integracoesClient): void
    {
        Log::info('Sincronizando cuidador com CRM', [
            'caregiver_id' => $this->caregiver->id,
            'action' => $this->action,
        ]);

        // Prepara payload
        $payload = $this->buildPayload();

        // Sincroniza com CRM
        $crmResult = $crmClient->syncCaregiver($payload);

        if (!$crmResult['ok']) {
            Log::warning('Falha ao sincronizar com CRM', [
                'caregiver_id' => $this->caregiver->id,
                'response' => $crmResult,
            ]);
        }

        // Publica evento no hub de integracoes
        $this->publishEvent($integracoesClient);

        Log::info('Sincronizacao com CRM concluida', [
            'caregiver_id' => $this->caregiver->id,
            'action' => $this->action,
        ]);
    }

    private function buildPayload(): array
    {
        return [
            'caregiver_id' => $this->caregiver->id,
            'name' => $this->caregiver->name,
            'phone' => $this->caregiver->phone,
            'email' => $this->caregiver->email,
            'city' => $this->caregiver->city,
            'status' => $this->caregiver->status?->code,
            'experience_years' => $this->caregiver->experience_years,
            'skills' => $this->caregiver->skills->map(fn ($s) => [
                'care_type' => $s->careType?->code,
                'level' => $s->level?->code,
            ])->toArray(),
            'regions' => $this->caregiver->regions->map(fn ($r) => [
                'city' => $r->city,
                'neighborhood' => $r->neighborhood,
            ])->toArray(),
            'action' => $this->action,
            'synced_at' => now()->toIso8601String(),
        ];
    }

    private function publishEvent(IntegracoesClient $client): void
    {
        $data = [
            'name' => $this->caregiver->name,
            'phone' => $this->caregiver->phone,
            'city' => $this->caregiver->city,
            'status' => $this->caregiver->status?->code,
        ];

        match ($this->action) {
            'created' => $client->caregiverCreated($this->caregiver->id, $data),
            'activated' => $client->caregiverActivated($this->caregiver->id, $data),
            'deactivated' => $client->caregiverDeactivated($this->caregiver->id),
            'status_changed' => $client->publishEvent('caregiver.status_changed', [
                'caregiver_id' => $this->caregiver->id,
                ...$data,
            ]),
            default => $client->publishEvent("caregiver.{$this->action}", [
                'caregiver_id' => $this->caregiver->id,
                ...$data,
            ]),
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job de sincronizacao CRM falhou', [
            'caregiver_id' => $this->caregiver->id,
            'action' => $this->action,
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return [
            'crm-sync',
            'caregiver:' . $this->caregiver->id,
            'action:' . $this->action,
        ];
    }
}
