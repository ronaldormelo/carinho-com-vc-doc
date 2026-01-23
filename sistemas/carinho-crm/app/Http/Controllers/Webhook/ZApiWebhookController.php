<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Domain\DomainInteractionChannel;
use App\Services\InteractionService;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para webhooks da Z-API (WhatsApp)
 */
class ZApiWebhookController extends Controller
{
    public function __construct(
        protected InteractionService $interactionService,
        protected LeadService $leadService
    ) {}

    /**
     * Recebe mensagens do WhatsApp
     */
    public function message(Request $request)
    {
        Log::channel('whatsapp')->info('Z-API webhook message', $request->all());

        try {
            $data = $request->all();

            // Extrai dados da mensagem
            $phone = $data['phone'] ?? null;
            $message = $data['text']['message'] ?? $data['caption'] ?? '';
            $isFromMe = $data['isFromMe'] ?? false;
            $messageId = $data['messageId'] ?? null;

            if (!$phone) {
                return response()->json(['status' => 'ignored', 'reason' => 'no_phone']);
            }

            // Formata telefone (remove @c.us e outros sufixos)
            $phone = $this->formatPhoneFromWebhook($phone);

            // Busca lead pelo telefone
            $lead = $this->findLeadByPhone($phone);

            if ($lead) {
                // Registra interação
                $direction = $isFromMe ? 'Enviada' : 'Recebida';
                $summary = "[WhatsApp {$direction}] {$message}";

                $this->interactionService->createInteraction([
                    'lead_id' => $lead->id,
                    'channel_id' => DomainInteractionChannel::WHATSAPP,
                    'summary' => $summary,
                    'occurred_at' => now(),
                ]);

                Log::channel('whatsapp')->info('Interação registrada', [
                    'lead_id' => $lead->id,
                    'direction' => $direction,
                ]);
            } else {
                Log::channel('whatsapp')->info('Lead não encontrado para telefone', [
                    'phone' => $phone,
                ]);
            }

            return response()->json(['status' => 'processed']);

        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Erro ao processar webhook', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Recebe status de mensagens (entregue, lida, etc)
     */
    public function status(Request $request)
    {
        Log::channel('whatsapp')->debug('Z-API webhook status', $request->all());

        // Processa status de mensagem (opcional: pode atualizar status de envio)
        $data = $request->all();
        $status = $data['status'] ?? null;
        $messageId = $data['messageId'] ?? null;

        // Log para rastreamento
        if ($status && $messageId) {
            Log::channel('whatsapp')->info("Mensagem {$messageId}: {$status}");
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Recebe eventos de conexão
     */
    public function connection(Request $request)
    {
        Log::channel('whatsapp')->info('Z-API webhook connection', $request->all());

        $connected = $request->input('connected', false);
        $reason = $request->input('reason');

        if (!$connected) {
            Log::channel('whatsapp')->warning('WhatsApp desconectado', [
                'reason' => $reason,
            ]);

            // Aqui pode-se enviar alerta para administradores
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Busca lead pelo telefone (descriptografado)
     */
    protected function findLeadByPhone(string $phone): ?Lead
    {
        // Como o telefone está criptografado, precisamos buscar todos e filtrar
        // Em produção, considerar índice de hash ou busca otimizada
        return Lead::all()->first(function ($lead) use ($phone) {
            $leadPhone = preg_replace('/[^0-9]/', '', $lead->phone ?? '');
            return $leadPhone === $phone || 
                   str_ends_with($phone, $leadPhone) || 
                   str_ends_with($leadPhone, $phone);
        });
    }

    /**
     * Formata telefone vindo do webhook
     */
    protected function formatPhoneFromWebhook(string $phone): string
    {
        // Remove sufixos como @c.us, @s.whatsapp.net
        $phone = preg_replace('/@.*$/', '', $phone);
        // Remove caracteres não numéricos
        $phone = preg_replace('/[^0-9]/', '', $phone);

        return $phone;
    }
}
