<?php

namespace App\Http\Controllers;

use App\Integrations\WhatsApp\ZApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller para receber webhooks externos.
 */
class WebhookController extends Controller
{
    public function __construct(
        protected ZApiClient $zApiClient
    ) {}

    /**
     * Recebe webhook do Z-API (WhatsApp).
     */
    public function whatsapp(Request $request): JsonResponse
    {
        // Valida assinatura
        $signature = $request->header('X-Signature');
        $rawPayload = $request->getContent();

        if (!$this->zApiClient->isSignatureValid($rawPayload, $signature)) {
            Log::warning('Webhook WhatsApp com assinatura invalida');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->all();

        // Normaliza payload
        $normalized = $this->zApiClient->normalizeInbound($payload);

        Log::info('Webhook WhatsApp recebido', [
            'event' => $normalized['event'],
            'phone' => $normalized['phone'],
        ]);

        // Processa baseado no evento
        $event = $normalized['event'];

        match ($event) {
            'message' => $this->handleWhatsAppMessage($normalized),
            'status' => $this->handleWhatsAppStatus($normalized),
            'delivery' => $this->handleWhatsAppDelivery($normalized),
            default => Log::debug('Evento WhatsApp nao tratado', ['event' => $event]),
        };

        return response()->json(['received' => true]);
    }

    /**
     * Processa mensagem recebida do WhatsApp.
     */
    protected function handleWhatsAppMessage(array $data): void
    {
        $phone = $data['phone'];
        $body = $data['body'];
        $buttonResponse = $data['button_response'];

        // Se for resposta de botao, processa
        if ($buttonResponse) {
            $this->handleButtonResponse($phone, $buttonResponse);
            return;
        }

        // Log para processamento manual
        Log::info('Mensagem WhatsApp recebida', [
            'phone' => $phone,
            'body' => $body,
        ]);
    }

    /**
     * Processa resposta de botao.
     */
    protected function handleButtonResponse(string $phone, array $buttonResponse): void
    {
        $buttonId = $buttonResponse['id'] ?? null;

        Log::info('Resposta de botao WhatsApp', [
            'phone' => $phone,
            'button_id' => $buttonId,
        ]);

        // Processar acao baseada no botao
        // Ex: confirmar_agendamento, cancelar_agendamento, etc.
    }

    /**
     * Processa status de mensagem.
     */
    protected function handleWhatsAppStatus(array $data): void
    {
        Log::debug('Status WhatsApp', $data);
    }

    /**
     * Processa confirmacao de entrega.
     */
    protected function handleWhatsAppDelivery(array $data): void
    {
        Log::debug('Delivery WhatsApp', $data);
    }

    /**
     * Webhook do sistema de Atendimento.
     */
    public function atendimento(Request $request): JsonResponse
    {
        $event = $request->input('event');
        $data = $request->input('data');

        Log::info('Webhook Atendimento recebido', [
            'event' => $event,
        ]);

        match ($event) {
            'demanda_criada' => $this->handleDemandaCriada($data),
            'demanda_atualizada' => $this->handleDemandaAtualizada($data),
            'demanda_cancelada' => $this->handleDemandaCancelada($data),
            default => Log::debug('Evento Atendimento nao tratado', ['event' => $event]),
        };

        return response()->json(['received' => true]);
    }

    /**
     * Processa nova demanda.
     */
    protected function handleDemandaCriada(array $data): void
    {
        // Importa demanda como solicitacao de servico
        // app(ServiceRequestService::class)->importFromAtendimento($data['id']);

        Log::info('Demanda criada recebida', $data);
    }

    /**
     * Processa atualizacao de demanda.
     */
    protected function handleDemandaAtualizada(array $data): void
    {
        Log::info('Demanda atualizada recebida', $data);
    }

    /**
     * Processa cancelamento de demanda.
     */
    protected function handleDemandaCancelada(array $data): void
    {
        Log::info('Demanda cancelada recebida', $data);
    }

    /**
     * Webhook do sistema de Cuidadores.
     */
    public function cuidadores(Request $request): JsonResponse
    {
        $event = $request->input('event');
        $data = $request->input('data');

        Log::info('Webhook Cuidadores recebido', [
            'event' => $event,
        ]);

        match ($event) {
            'disponibilidade_atualizada' => $this->handleDisponibilidadeAtualizada($data),
            'cuidador_indisponivel' => $this->handleCuidadorIndisponivel($data),
            default => Log::debug('Evento Cuidadores nao tratado', ['event' => $event]),
        };

        return response()->json(['received' => true]);
    }

    /**
     * Processa atualizacao de disponibilidade.
     */
    protected function handleDisponibilidadeAtualizada(array $data): void
    {
        Log::info('Disponibilidade atualizada', $data);
    }

    /**
     * Processa cuidador indisponivel.
     */
    protected function handleCuidadorIndisponivel(array $data): void
    {
        // Verificar agendamentos afetados e processar substituicao
        Log::warning('Cuidador indisponivel', $data);
    }

    /**
     * Health check do webhook.
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'carinho-operacao',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
