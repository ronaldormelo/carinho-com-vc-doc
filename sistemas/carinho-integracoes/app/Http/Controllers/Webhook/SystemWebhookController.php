<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\IntegrationEvent;
use App\Models\WebhookEndpoint;
use App\Services\EventProcessor;
use App\Jobs\ProcessLeadCreated;
use App\Jobs\ProcessClientRegistered;
use App\Jobs\ProcessServiceCompleted;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para webhooks de sistemas internos.
 *
 * Recebe eventos dos sistemas do ecossistema Carinho.
 */
class SystemWebhookController extends Controller
{
    public function __construct(
        private EventProcessor $eventProcessor
    ) {}

    /**
     * Recebe webhook generico de sistema interno.
     *
     * POST /webhooks/systems/{system}
     */
    public function handle(Request $request, string $system): JsonResponse
    {
        $rawPayload = $request->getContent();
        $signature = $request->header('X-Webhook-Signature');
        $eventType = $request->header('X-Event-Type');

        Log::info('System webhook received', [
            'system' => $system,
            'event_type' => $eventType,
            'ip' => $request->ip(),
        ]);

        // Valida assinatura se endpoint configurado
        $endpoint = WebhookEndpoint::where('system_name', $system)->active()->first();

        if ($endpoint && $signature) {
            if (!$endpoint->validateSignature($rawPayload, $signature)) {
                Log::warning('Invalid system webhook signature', [
                    'system' => $system,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'error' => 'Invalid signature',
                ], 401);
            }
        }

        $payload = $request->all();
        $eventType = $eventType ?? $payload['event_type'] ?? 'unknown';

        // Processa evento baseado no tipo
        $this->processEvent($system, $eventType, $payload);

        return response()->json([
            'status' => 'received',
            'event_type' => $eventType,
        ]);
    }

    /**
     * Recebe webhook do Site (leads).
     *
     * POST /webhooks/site/lead
     */
    public function siteLead(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Site lead webhook received', [
            'name' => $payload['name'] ?? 'unknown',
            'phone' => $payload['phone'] ?? 'unknown',
        ]);

        ProcessLeadCreated::dispatch([
            'name' => $payload['name'] ?? '',
            'phone' => $payload['phone'] ?? null,
            'email' => $payload['email'] ?? null,
            'source' => 'site',
            'message' => $payload['message'] ?? null,
            'utm_source' => $payload['utm_source'] ?? null,
            'utm_medium' => $payload['utm_medium'] ?? null,
            'utm_campaign' => $payload['utm_campaign'] ?? null,
            'page_url' => $payload['page_url'] ?? null,
            'form_id' => $payload['form_id'] ?? null,
        ]);

        return response()->json([
            'status' => 'received',
        ]);
    }

    /**
     * Recebe webhook do CRM (cliente cadastrado).
     *
     * POST /webhooks/crm/client-registered
     */
    public function crmClientRegistered(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('CRM client registered webhook received', [
            'client_id' => $payload['id'] ?? 'unknown',
        ]);

        ProcessClientRegistered::dispatch($payload);

        return response()->json([
            'status' => 'received',
        ]);
    }

    /**
     * Recebe webhook da Operacao (servico finalizado).
     *
     * POST /webhooks/operacao/service-completed
     */
    public function operacaoServiceCompleted(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Operacao service completed webhook received', [
            'service_id' => $payload['id'] ?? 'unknown',
        ]);

        ProcessServiceCompleted::dispatch($payload);

        return response()->json([
            'status' => 'received',
        ]);
    }

    /**
     * Recebe webhook do Financeiro (pagamento).
     *
     * POST /webhooks/financeiro/payment
     */
    public function financeiroPayment(Request $request): JsonResponse
    {
        $payload = $request->all();
        $status = $payload['status'] ?? 'unknown';

        Log::info('Financeiro payment webhook received', [
            'payment_id' => $payload['id'] ?? 'unknown',
            'status' => $status,
        ]);

        $eventType = $status === 'received'
            ? IntegrationEvent::TYPE_PAYMENT_RECEIVED
            : IntegrationEvent::TYPE_PAYMENT_FAILED;

        $this->eventProcessor->process(
            $eventType,
            IntegrationEvent::SOURCE_FINANCEIRO,
            $payload
        );

        return response()->json([
            'status' => 'received',
        ]);
    }

    /**
     * Recebe webhook do Financeiro (repasse processado).
     *
     * POST /webhooks/financeiro/payout
     */
    public function financeiroPayout(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Financeiro payout webhook received', [
            'payout_id' => $payload['id'] ?? 'unknown',
        ]);

        $this->eventProcessor->process(
            IntegrationEvent::TYPE_PAYOUT_PROCESSED,
            IntegrationEvent::SOURCE_FINANCEIRO,
            $payload
        );

        return response()->json([
            'status' => 'received',
        ]);
    }

    /**
     * Recebe webhook de Cuidadores (feedback).
     *
     * POST /webhooks/cuidadores/feedback
     */
    public function cuidadoresFeedback(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Cuidadores feedback webhook received', [
            'caregiver_id' => $payload['caregiver_id'] ?? 'unknown',
            'rating' => $payload['rating'] ?? 'unknown',
        ]);

        $this->eventProcessor->process(
            IntegrationEvent::TYPE_FEEDBACK_RECEIVED,
            IntegrationEvent::SOURCE_CUIDADORES,
            $payload
        );

        return response()->json([
            'status' => 'received',
        ]);
    }

    /**
     * Processa evento generico.
     */
    private function processEvent(string $source, string $eventType, array $payload): void
    {
        // Mapeia tipos de eventos para handlers especificos
        $handlers = [
            'lead.created' => fn ($p) => ProcessLeadCreated::dispatch($p),
            'client.registered' => fn ($p) => ProcessClientRegistered::dispatch($p),
            'service.completed' => fn ($p) => ProcessServiceCompleted::dispatch($p),
        ];

        if (isset($handlers[$eventType])) {
            $handlers[$eventType]($payload);
        } else {
            // Evento generico - usa processador padrao
            $this->eventProcessor->process($eventType, $source, $payload);
        }
    }
}
