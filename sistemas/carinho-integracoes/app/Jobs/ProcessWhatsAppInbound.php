<?php

namespace App\Jobs;

use App\Models\IntegrationEvent;
use App\Services\Integrations\Crm\CrmClient;
use App\Services\Integrations\Atendimento\AtendimentoClient;
use App\Services\Integrations\WhatsApp\ZApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para processamento de mensagem WhatsApp recebida.
 *
 * Fluxo: Mensagem recebida -> Registro no CRM + Encaminhamento para atendimento
 */
class ProcessWhatsAppInbound implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Cria nova instancia do job.
     */
    public function __construct(
        private array $messageData
    ) {
        $this->onQueue('integrations-high');
    }

    /**
     * Executa o job.
     */
    public function handle(
        CrmClient $crm,
        AtendimentoClient $atendimento,
        ZApiClient $zapi
    ): void {
        Log::info('Processing WhatsApp inbound message', [
            'phone' => $this->messageData['phone'],
            'event' => $this->messageData['event'] ?? 'message',
        ]);

        // Ignora mensagens proprias
        if ($this->messageData['is_from_me'] ?? false) {
            Log::info('Ignoring own message');
            return;
        }

        // Ignora eventos de status (apenas mensagens)
        $event = $this->messageData['event'] ?? 'message';
        if (!in_array($event, ['message', 'receivedMessage'])) {
            Log::info('Ignoring non-message event', ['event' => $event]);
            return;
        }

        $phone = $this->messageData['phone'];
        $body = $this->messageData['body'] ?? '';
        $name = $this->messageData['name'] ?? '';

        // 1. Verifica se e resposta de botao (feedback)
        if (!empty($this->messageData['button_response'])) {
            $this->handleButtonResponse($crm);
            return;
        }

        // 2. Busca ou cria lead no CRM
        $leadResponse = $crm->findLeadByPhone($phone);
        $lead = null;

        if ($leadResponse['ok'] && !empty($leadResponse['body']['data'])) {
            $lead = $leadResponse['body']['data'][0];

            // Registra interacao
            $crm->registerInteraction($lead['id'], [
                'channel' => 'whatsapp',
                'direction' => 'inbound',
                'content' => $body,
                'metadata' => [
                    'message_id' => $this->messageData['message_id'] ?? null,
                    'received_at' => $this->messageData['received_at'] ?? now()->toIso8601String(),
                ],
            ]);

            Log::info('Interaction registered for existing lead', [
                'lead_id' => $lead['id'],
            ]);
        } else {
            // Novo lead via WhatsApp - cria e envia resposta automatica
            ProcessLeadCreated::dispatch([
                'name' => $name,
                'phone' => $phone,
                'source' => 'whatsapp',
                'message' => $body,
            ]);

            Log::info('New lead created from WhatsApp', [
                'phone' => $phone,
            ]);
        }

        // 3. Encaminha para sistema de atendimento
        $conversationResponse = $atendimento->findConversationByPhone($phone);

        if ($conversationResponse['ok'] && !empty($conversationResponse['body']['data'])) {
            // Adiciona mensagem a conversa existente
            $conversation = $conversationResponse['body']['data'][0];

            $atendimento->addMessage($conversation['id'], [
                'content' => $body,
                'direction' => 'inbound',
                'channel' => 'whatsapp',
                'sender_phone' => $phone,
                'sender_name' => $name,
                'received_at' => $this->messageData['received_at'] ?? now()->toIso8601String(),
            ]);
        } else {
            // Cria nova conversa
            $atendimento->createConversation([
                'phone' => $phone,
                'name' => $name,
                'channel' => 'whatsapp',
                'crm_lead_id' => $lead['id'] ?? null,
                'initial_message' => $body,
            ]);
        }

        // 4. Registra evento de integracao
        IntegrationEvent::createEvent(
            IntegrationEvent::TYPE_WHATSAPP_INBOUND,
            IntegrationEvent::SOURCE_WHATSAPP,
            [
                'phone' => $phone,
                'name' => $name,
                'body' => $body,
                'lead_id' => $lead['id'] ?? null,
            ]
        );

        Log::info('WhatsApp inbound processing completed', [
            'phone' => $phone,
        ]);
    }

    /**
     * Processa resposta de botao (feedback).
     */
    private function handleButtonResponse(CrmClient $crm): void
    {
        $buttonId = $this->messageData['button_response']['id'] ?? '';

        // Verifica se e rating de feedback
        if (str_starts_with($buttonId, 'rating_')) {
            $rating = (int) str_replace('rating_', '', $buttonId);

            Log::info('Feedback rating received', [
                'phone' => $this->messageData['phone'],
                'rating' => $rating,
            ]);

            // Busca lead
            $leadResponse = $crm->findLeadByPhone($this->messageData['phone']);

            if ($leadResponse['ok'] && !empty($leadResponse['body']['data'])) {
                $lead = $leadResponse['body']['data'][0];

                // Registra feedback
                $crm->dispatchEvent('feedback.received', [
                    'lead_id' => $lead['id'],
                    'phone' => $this->messageData['phone'],
                    'rating' => $rating,
                    'channel' => 'whatsapp',
                ]);

                // Envia agradecimento
                SendWhatsAppMessage::dispatch('text', [
                    'phone' => $this->messageData['phone'],
                    'message' => $rating >= 4
                        ? 'Muito obrigado pela avaliação! 😊 Ficamos felizes em saber que você está satisfeito com nosso serviço.'
                        : 'Agradecemos seu feedback. Vamos trabalhar para melhorar nosso atendimento. Se precisar de algo, estamos à disposição.',
                ]);
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('WhatsApp inbound processing failed', [
            'phone' => $this->messageData['phone'] ?? 'unknown',
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
            'inbound',
            'phone:' . ($this->messageData['phone'] ?? 'unknown'),
        ];
    }
}
