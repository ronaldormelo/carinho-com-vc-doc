<?php

namespace App\Jobs;

use App\Models\IntegrationEvent;
use App\Services\Integrations\Crm\CrmClient;
use App\Services\Integrations\Atendimento\AtendimentoClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Fluxo: mensagem WhatsApp → lead público no CRM + inbox no Atendimento.
 */
class ProcessWhatsAppInbound implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        private array $messageData
    ) {
        $this->onQueue('integrations-high');
    }

    public function handle(
        CrmClient $crm,
        AtendimentoClient $atendimento
    ): void {
        Log::info('Processing WhatsApp inbound message', [
            'event' => $this->messageData['event'] ?? 'message',
        ]);

        if ($this->messageData['is_from_me'] ?? false) {
            return;
        }

        $event = $this->messageData['event'] ?? 'message';
        if (!in_array($event, ['message', 'receivedMessage'])) {
            return;
        }

        if (!empty($this->messageData['button_response'])) {
            $this->handleButtonResponse($crm);
            return;
        }

        $phone = $this->messageData['phone'];
        $body = $this->messageData['body'] ?? '';
        $name = $this->messageData['name'] ?? 'Contato WhatsApp';

        $leadResponse = $crm->createLead([
            'name' => $name,
            'phone' => $phone,
            'source' => 'whatsapp',
        ]);

        $leadId = $leadResponse['body']['data']['id'] ?? $leadResponse['body']['id'] ?? null;

        if ($leadId) {
            $crm->registerInteraction((int) $leadId, [
                'channel' => 'whatsapp',
                'content' => $body,
            ]);
        }

        $atendimento->createConversation([
            'phone' => $phone,
            'name' => $name,
            'initial_message' => $body,
            'crm_lead_id' => $leadId,
        ]);

        IntegrationEvent::createEvent(
            IntegrationEvent::TYPE_WHATSAPP_INBOUND,
            IntegrationEvent::SOURCE_WHATSAPP,
            [
                'phone' => $phone,
                'name' => $name,
                'body' => $body,
                'lead_id' => $leadId,
            ]
        );
    }

    private function handleButtonResponse(CrmClient $crm): void
    {
        $buttonId = $this->messageData['button_response']['id'] ?? '';

        if (!str_starts_with($buttonId, 'rating_')) {
            return;
        }

        $rating = (int) str_replace('rating_', '', $buttonId);
        $phone = $this->messageData['phone'];

        $leadResponse = $crm->createLead([
            'name' => $this->messageData['name'] ?? 'Contato WhatsApp',
            'phone' => $phone,
            'source' => 'whatsapp',
        ]);

        $leadId = $leadResponse['body']['data']['id'] ?? $leadResponse['body']['id'] ?? null;

        if ($leadId) {
            $crm->registerInteraction((int) $leadId, [
                'channel' => 'whatsapp',
                'content' => "Feedback WhatsApp: {$rating}/5",
            ]);
        }

        SendWhatsAppMessage::dispatch('text', [
            'phone' => $phone,
            'message' => $rating >= 4
                ? 'Muito obrigado pela avaliação! 😊 Ficamos felizes em saber que você está satisfeito com nosso serviço.'
                : 'Agradecemos seu feedback. Vamos trabalhar para melhorar nosso atendimento. Se precisar de algo, estamos à disposição.',
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('WhatsApp inbound processing failed', [
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return [
            'whatsapp',
            'inbound',
            'phone:' . ($this->messageData['phone'] ?? 'unknown'),
        ];
    }
}
