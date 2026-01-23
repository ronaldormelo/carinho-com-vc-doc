<?php

namespace App\Jobs;

use App\Models\FormSubmission;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para enviar notificacao de novo lead.
 */
class SendLeadNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Tentativas maximas.
     */
    public int $tries = 3;

    /**
     * Backoff.
     */
    public array $backoff = [30, 60, 120];

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
    public function handle(WhatsAppService $whatsapp): void
    {
        // Verifica se WhatsApp esta habilitado
        if (!config('integrations.whatsapp.enabled')) {
            Log::info('WhatsApp desabilitado, pulando notificacao');
            return;
        }

        Log::info('Enviando notificacao de novo lead', [
            'submission_id' => $this->submission->id,
            'type' => $this->type,
        ]);

        // Envia notificacao para equipe de atendimento
        $urgencyLabel = $this->submission->urgency->label ?? 'Sem data definida';

        $response = $whatsapp->sendNewLeadNotification(
            $this->submission->phone,
            $this->submission->name,
            $urgencyLabel
        );

        if (!$response['ok']) {
            Log::warning('Falha ao enviar notificacao de lead', [
                'submission_id' => $this->submission->id,
                'error' => $response['error'] ?? 'Unknown',
            ]);
        }

        // Se urgencia for "hoje", envia tambem mensagem de boas-vindas
        if ($this->submission->urgency_id === 1 && $this->type === 'cliente') {
            $whatsapp->sendWelcomeMessage(
                $this->submission->phone,
                $this->submission->name
            );
        }
    }

    /**
     * Job falhou.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job SendLeadNotification falhou', [
            'submission_id' => $this->submission->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
