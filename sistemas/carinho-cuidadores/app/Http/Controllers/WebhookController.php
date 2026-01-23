<?php

namespace App\Http\Controllers;

use App\Integrations\WhatsApp\ZApiClient;
use App\Jobs\ProcessCaregiverMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private ZApiClient $zApiClient
    ) {}

    /**
     * Recebe webhooks do Z-API (WhatsApp).
     */
    public function whatsapp(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $signature = $request->header('X-Zapi-Signature')
            ?? $request->header('X-Webhook-Signature');

        // Valida assinatura se configurada
        if (!$this->zApiClient->isSignatureValid($rawPayload, $signature)) {
            Log::warning('Webhook WhatsApp: assinatura invalida', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        $normalized = $this->zApiClient->normalizeInbound($payload);

        Log::info('Webhook WhatsApp recebido', [
            'phone' => $normalized['phone'],
            'event' => $normalized['event'],
        ]);

        // Processa mensagem de forma assincrona
        if ($normalized['event'] === 'message' || $normalized['event'] === 'ReceivedCallback') {
            ProcessCaregiverMessage::dispatch($normalized);
        }

        return response()->json(['status' => 'received']);
    }

    /**
     * Recebe webhooks do sistema de Documentos/LGPD.
     */
    public function documents(Request $request): JsonResponse
    {
        $token = $request->header('X-Internal-Token');
        $expectedToken = config('integrations.internal.token');

        if ($token !== $expectedToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $event = $payload['event'] ?? null;

        Log::info('Webhook Documentos recebido', [
            'event' => $event,
            'document_id' => $payload['document_id'] ?? null,
        ]);

        // Processa eventos de documentos
        match ($event) {
            'document.signed' => $this->handleDocumentSigned($payload),
            'document.rejected' => $this->handleDocumentRejected($payload),
            default => null,
        };

        return response()->json(['status' => 'received']);
    }

    /**
     * Recebe webhooks do sistema de Operacao.
     */
    public function operacao(Request $request): JsonResponse
    {
        $token = $request->header('X-Internal-Token');
        $expectedToken = config('integrations.internal.token');

        if ($token !== $expectedToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $event = $payload['event'] ?? null;

        Log::info('Webhook Operacao recebido', [
            'event' => $event,
        ]);

        // Processa eventos de operacao
        match ($event) {
            'service.completed' => $this->handleServiceCompleted($payload),
            'service.started' => $this->handleServiceStarted($payload),
            default => null,
        };

        return response()->json(['status' => 'received']);
    }

    private function handleDocumentSigned(array $payload): void
    {
        // Atualiza contrato quando documento e assinado
        $contractId = $payload['reference_id'] ?? null;
        if ($contractId) {
            // Implementar logica de atualizacao
            Log::info('Contrato assinado', ['contract_id' => $contractId]);
        }
    }

    private function handleDocumentRejected(array $payload): void
    {
        // Notifica sobre documento rejeitado
        $documentId = $payload['document_id'] ?? null;
        if ($documentId) {
            Log::info('Documento rejeitado via webhook', ['document_id' => $documentId]);
        }
    }

    private function handleServiceCompleted(array $payload): void
    {
        // Dispara solicitacao de avaliacao
        $serviceId = $payload['service_id'] ?? null;
        $caregiverId = $payload['caregiver_id'] ?? null;

        if ($serviceId && $caregiverId) {
            Log::info('Servico completado', [
                'service_id' => $serviceId,
                'caregiver_id' => $caregiverId,
            ]);
            // Disparar job para solicitar avaliacao
        }
    }

    private function handleServiceStarted(array $payload): void
    {
        // Registra inicio de servico
        $serviceId = $payload['service_id'] ?? null;
        $caregiverId = $payload['caregiver_id'] ?? null;

        if ($serviceId && $caregiverId) {
            Log::info('Servico iniciado', [
                'service_id' => $serviceId,
                'caregiver_id' => $caregiverId,
            ]);
        }
    }
}
