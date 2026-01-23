<?php

namespace App\Jobs;

use App\Services\Integrations\WhatsApp\ZApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para envio de mensagens WhatsApp via Z-API.
 *
 * Processa envio assincrono de mensagens com retry
 * e logging completo.
 */
class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public array $backoff = [5, 15, 60];

    /**
     * Tipo de mensagem.
     */
    private string $type;

    /**
     * Dados da mensagem.
     */
    private array $data;

    /**
     * Cria nova instancia do job.
     */
    public function __construct(string $type, array $data)
    {
        $this->type = $type;
        $this->data = $data;
        $this->onQueue('notifications');
    }

    /**
     * Executa o job.
     */
    public function handle(ZApiClient $zapi): void
    {
        Log::info('Sending WhatsApp message', [
            'type' => $this->type,
            'phone' => $this->data['phone'] ?? 'unknown',
        ]);

        $result = match ($this->type) {
            'text' => $zapi->sendTextMessage(
                $this->data['phone'],
                $this->data['message']
            ),
            'welcome' => $zapi->sendWelcomeMessage(
                $this->data['phone'],
                $this->data['name']
            ),
            'lead_response' => $zapi->sendLeadAutoResponse(
                $this->data['phone'],
                $this->data['name']
            ),
            'feedback_request' => $zapi->sendFeedbackRequest(
                $this->data['phone'],
                $this->data['client_name'],
                $this->data['caregiver_name']
            ),
            'service_completed' => $zapi->sendServiceCompletedNotification(
                $this->data['phone'],
                $this->data['name']
            ),
            'media' => $zapi->sendMediaMessage(
                $this->data['phone'],
                $this->data['media_url'],
                $this->data['caption'] ?? null
            ),
            'document' => $zapi->sendDocument(
                $this->data['phone'],
                $this->data['document_url'],
                $this->data['file_name']
            ),
            'buttons' => $zapi->sendButtonList(
                $this->data['phone'],
                $this->data['message'],
                $this->data['buttons']
            ),
            'link' => $zapi->sendLink(
                $this->data['phone'],
                $this->data['message'],
                $this->data['url'],
                $this->data['title'] ?? null
            ),
            default => throw new \InvalidArgumentException("Unknown message type: {$this->type}"),
        };

        if (!$result['ok']) {
            Log::warning('WhatsApp message failed', [
                'type' => $this->type,
                'phone' => $this->data['phone'] ?? 'unknown',
                'error' => $result['error'] ?? 'Unknown error',
            ]);

            throw new \Exception($result['error'] ?? 'Failed to send WhatsApp message');
        }

        Log::info('WhatsApp message sent', [
            'type' => $this->type,
            'phone' => $this->data['phone'] ?? 'unknown',
            'status' => $result['status'],
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('WhatsApp message failed permanently', [
            'type' => $this->type,
            'phone' => $this->data['phone'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Tags para monitoramento.
     */
    public function tags(): array
    {
        return [
            'whatsapp',
            'type:' . $this->type,
        ];
    }
}
