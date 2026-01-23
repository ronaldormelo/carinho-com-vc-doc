<?php

namespace App\Jobs;

use App\Integrations\WhatsApp\ZApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para envio de notificacoes via WhatsApp.
 */
class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero de tentativas.
     */
    public int $tries = 3;

    /**
     * Tempo de backoff entre tentativas (segundos).
     */
    public array $backoff = [5, 15, 30];

    /**
     * Cria nova instancia do job.
     */
    public function __construct(
        public string $phone,
        public string $type,
        public array $data = []
    ) {
        $this->onQueue('whatsapp');
    }

    /**
     * Executa o job.
     */
    public function handle(ZApiClient $zApiClient): void
    {
        Log::info('Enviando notificacao WhatsApp', [
            'phone' => $this->phone,
            'type' => $this->type,
        ]);

        $result = match ($this->type) {
            'service_start' => $zApiClient->sendServiceStartNotification($this->phone, $this->data),
            'service_end' => $zApiClient->sendServiceEndNotification($this->phone, $this->data),
            'schedule_reminder' => $zApiClient->sendScheduleReminder($this->phone, $this->data),
            'caregiver_assigned' => $zApiClient->sendCaregiverAssignedNotification($this->phone, $this->data),
            'replacement' => $zApiClient->sendReplacementNotification($this->phone, $this->data),
            'schedule_confirmation' => $zApiClient->sendScheduleConfirmation($this->phone, $this->data),
            'text' => $zApiClient->sendTextMessage($this->phone, $this->data['message'] ?? ''),
            default => throw new \InvalidArgumentException("Tipo de notificacao nao suportado: {$this->type}"),
        };

        if (!$result['ok']) {
            throw new \RuntimeException('Falha ao enviar WhatsApp: ' . ($result['error'] ?? 'Unknown error'));
        }

        Log::info('Notificacao WhatsApp enviada', [
            'phone' => $this->phone,
            'type' => $this->type,
        ]);
    }

    /**
     * Trata falha do job.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de WhatsApp falhou', [
            'phone' => $this->phone,
            'type' => $this->type,
            'error' => $exception->getMessage(),
        ]);
    }
}
