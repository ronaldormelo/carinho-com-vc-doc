<?php

namespace App\Jobs;

use App\Models\FormSubmission;
use App\Services\CrmClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para sincronizar lead com o CRM via POST /api/v1/public/leads.
 */
class SyncLeadToCrm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [60, 120, 300, 600, 1200];

    public function __construct(
        private FormSubmission $submission,
        private string $type = 'cliente'
    ) {}

    public function handle(CrmClient $crm): void
    {
        Log::info('Sincronizando lead com CRM', [
            'submission_id' => $this->submission->id,
            'type' => $this->type,
        ]);

        $response = $crm->createLead($this->buildLeadData());

        if (!$response['ok']) {
            Log::error('Falha ao sincronizar lead com CRM', [
                'submission_id' => $this->submission->id,
                'status' => $response['status'] ?? null,
                'error' => $response['error'] ?? 'Unknown error',
            ]);

            throw new \Exception('Falha ao sincronizar com CRM: ' . ($response['error'] ?? 'Unknown'));
        }

        Log::info('Lead sincronizado no CRM', [
            'submission_id' => $this->submission->id,
            'lead_id' => $response['data']['data']['id'] ?? $response['data']['id'] ?? null,
        ]);

        $this->submission->markAsSynced();
    }

    private function buildLeadData(): array
    {
        $data = [
            'name' => $this->submission->name,
            'phone' => $this->submission->phone,
            'email' => $this->submission->email,
            'city' => $this->submission->city ?: 'nao_informada',
            'urgency_id' => $this->submission->urgency_id,
            'service_type_id' => $this->submission->service_type_id,
            'origin' => 'site',
            'source' => $this->type === 'cuidador' ? 'site_cuidador' : 'site',
            'utm_id' => $this->submission->utm_id,
        ];

        return array_filter($data, fn ($value) => $value !== null && $value !== '');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job SyncLeadToCrm falhou definitivamente', [
            'submission_id' => $this->submission->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
