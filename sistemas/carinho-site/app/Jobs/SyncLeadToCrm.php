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
 * Job para sincronizar lead com o CRM.
 */
class SyncLeadToCrm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Tentativas maximas.
     */
    public int $tries = 5;

    /**
     * Backoff exponencial.
     */
    public array $backoff = [60, 120, 300, 600, 1200];

    /**
     * Cria uma nova instancia do job.
     */
    public function __construct(
        private FormSubmission $submission,
        private string $type = 'cliente'
    ) {}

    /**
     * Executa o job.
     */
    public function handle(CrmClient $crm): void
    {
        Log::info('Sincronizando lead com CRM', [
            'submission_id' => $this->submission->id,
            'type' => $this->type,
        ]);

        // Monta dados do lead
        $leadData = $this->buildLeadData();

        // Verifica se ja existe lead com este telefone
        $existingLead = $crm->findLeadByPhone($this->submission->phone);

        if ($existingLead) {
            // Atualiza lead existente
            $response = $crm->updateLead($existingLead['id'], $leadData);

            if ($response['ok']) {
                Log::info('Lead atualizado no CRM', [
                    'submission_id' => $this->submission->id,
                    'lead_id' => $existingLead['id'],
                ]);
            }
        } else {
            // Cria novo lead
            $response = $crm->createLead($leadData);

            if ($response['ok'] && isset($response['data']['id'])) {
                Log::info('Lead criado no CRM', [
                    'submission_id' => $this->submission->id,
                    'lead_id' => $response['data']['id'],
                ]);

                // Registra origem se houver UTM
                if ($this->submission->utm_id) {
                    $crm->registerLeadSource($response['data']['id'], [
                        'source' => $this->submission->utm->source ?? 'site',
                        'medium' => $this->submission->utm->medium ?? 'organic',
                        'campaign' => $this->submission->utm->campaign ?? '',
                        'content' => $this->submission->utm->content ?? '',
                        'term' => $this->submission->utm->term ?? '',
                    ]);
                }
            }
        }

        if (!$response['ok']) {
            Log::error('Falha ao sincronizar lead com CRM', [
                'submission_id' => $this->submission->id,
                'error' => $response['error'] ?? 'Unknown error',
            ]);

            // Re-throw para retry
            throw new \Exception('Falha ao sincronizar com CRM: ' . ($response['error'] ?? 'Unknown'));
        }

        // Marca como sincronizado
        $this->submission->markAsSynced();
    }

    /**
     * Monta dados do lead para o CRM.
     */
    private function buildLeadData(): array
    {
        $data = [
            'name' => $this->submission->name,
            'phone' => $this->submission->phone,
            'email' => $this->submission->email,
            'city' => $this->submission->city,
            'urgency' => $this->submission->urgency->code ?? 'sem_data',
            'service_type' => $this->submission->serviceType->code ?? 'horista',
            'origin' => 'site',
            'type' => $this->type,
        ];

        // Adiciona dados adicionais do payload
        $payload = $this->submission->payload_json ?? [];

        if ($this->type === 'cliente') {
            $data['patient_name'] = $payload['patient_name'] ?? null;
            $data['patient_condition'] = $payload['patient_condition'] ?? null;
            $data['preferred_schedule'] = $payload['preferred_schedule'] ?? null;
            $data['neighborhood'] = $payload['neighborhood'] ?? null;
        } else {
            $data['experience_years'] = $payload['experience_years'] ?? null;
            $data['has_course'] = $payload['has_course'] ?? false;
            $data['specialties'] = $payload['specialties'] ?? [];
            $data['availability'] = $payload['availability'] ?? null;
        }

        if (!empty($payload['message'])) {
            $data['notes'] = $payload['message'];
        }

        // Adiciona consentimento LGPD
        if ($this->submission->consent_at) {
            $data['consent'] = [
                'granted_at' => $this->submission->consent_at->toIso8601String(),
                'ip_address' => $this->submission->ip_address,
            ];
        }

        return $data;
    }

    /**
     * Job falhou apos todas as tentativas.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job SyncLeadToCrm falhou definitivamente', [
            'submission_id' => $this->submission->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
