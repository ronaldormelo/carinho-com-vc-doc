<?php

namespace App\Services;

use App\Models\IntegrationEvent;
use App\Models\EventMapping;
use App\Models\WebhookEndpoint;
use App\Models\WebhookDelivery;
use App\Models\RetryQueue;
use App\Models\DeadLetter;
use App\Jobs\ProcessEvent;
use App\Jobs\DeliverWebhook;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Servico central de processamento de eventos.
 *
 * Orquestra o fluxo de eventos entre sistemas:
 * 1. Recebe evento
 * 2. Valida e persiste
 * 3. Aplica mapeamentos
 * 4. Dispara para sistemas alvo
 * 5. Gerencia retry e DLQ
 */
class EventProcessor
{
    /**
     * Registra e processa novo evento.
     */
    public function process(string $eventType, string $sourceSystem, array $payload): IntegrationEvent
    {
        // Verifica idempotencia
        $idempotencyKey = $payload['idempotency_key'] ?? null;

        if ($idempotencyKey) {
            $existing = IntegrationEvent::where('event_type', $eventType)
                ->where('source_system', $sourceSystem)
                ->whereJsonContains('payload_json->idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                Log::info('Duplicate event ignored', [
                    'event_type' => $eventType,
                    'idempotency_key' => $idempotencyKey,
                ]);

                return $existing;
            }
        }

        // Cria evento
        $event = IntegrationEvent::createEvent($eventType, $sourceSystem, $payload);

        Log::info('Event created', [
            'event_id' => $event->id,
            'event_type' => $eventType,
            'source' => $sourceSystem,
        ]);

        // Despacha para processamento assincrono
        ProcessEvent::dispatch($event)->onQueue('integrations');

        return $event;
    }

    /**
     * Executa processamento do evento.
     */
    public function execute(IntegrationEvent $event): void
    {
        try {
            $event->markAsProcessing();

            // Busca endpoints alvo para este tipo de evento
            $endpoints = $this->getTargetEndpoints($event);

            if ($endpoints->isEmpty()) {
                Log::warning('No target endpoints for event', [
                    'event_id' => $event->id,
                    'event_type' => $event->event_type,
                ]);

                $event->markAsDone();
                return;
            }

            // Cria entregas para cada endpoint
            foreach ($endpoints as $endpoint) {
                $this->scheduleDelivery($event, $endpoint);
            }

            $event->markAsDone();
        } catch (\Throwable $e) {
            Log::error('Event processing failed', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            $this->handleFailure($event, $e->getMessage());
        }
    }

    /**
     * Busca endpoints alvo para o tipo de evento.
     */
    private function getTargetEndpoints(IntegrationEvent $event)
    {
        // Mapeamento de tipos de evento para sistemas alvo
        $targetSystems = $this->getTargetSystems($event->event_type);

        return WebhookEndpoint::active()
            ->whereIn('system_name', $targetSystems)
            ->get();
    }

    /**
     * Retorna sistemas alvo para um tipo de evento.
     */
    private function getTargetSystems(string $eventType): array
    {
        // Mapeamento de eventos para sistemas
        $mapping = [
            // Eventos de Lead
            IntegrationEvent::TYPE_LEAD_CREATED => ['crm', 'marketing'],
            IntegrationEvent::TYPE_LEAD_UPDATED => ['crm'],

            // Eventos de Cliente
            IntegrationEvent::TYPE_CLIENT_REGISTERED => ['crm', 'financeiro', 'marketing'],
            IntegrationEvent::TYPE_CLIENT_UPDATED => ['crm', 'operacao'],

            // Eventos de Servico
            IntegrationEvent::TYPE_SERVICE_SCHEDULED => ['crm', 'operacao', 'cuidadores', 'financeiro'],
            IntegrationEvent::TYPE_SERVICE_STARTED => ['crm', 'operacao'],
            IntegrationEvent::TYPE_SERVICE_COMPLETED => ['crm', 'financeiro', 'cuidadores'],
            IntegrationEvent::TYPE_SERVICE_CANCELLED => ['crm', 'operacao', 'financeiro'],

            // Eventos Financeiros
            IntegrationEvent::TYPE_PAYMENT_RECEIVED => ['crm', 'operacao'],
            IntegrationEvent::TYPE_PAYMENT_FAILED => ['crm'],
            IntegrationEvent::TYPE_INVOICE_CREATED => ['crm'],
            IntegrationEvent::TYPE_PAYOUT_PROCESSED => ['cuidadores'],

            // Eventos WhatsApp
            IntegrationEvent::TYPE_WHATSAPP_INBOUND => ['crm', 'atendimento'],
            IntegrationEvent::TYPE_WHATSAPP_STATUS => ['atendimento'],

            // Eventos de Feedback
            IntegrationEvent::TYPE_FEEDBACK_RECEIVED => ['crm', 'cuidadores'],

            // Eventos de Cuidador
            IntegrationEvent::TYPE_CAREGIVER_AVAILABLE => ['operacao'],
            IntegrationEvent::TYPE_CAREGIVER_ASSIGNED => ['crm', 'cuidadores'],
        ];

        return $mapping[$eventType] ?? [];
    }

    /**
     * Agenda entrega de webhook.
     */
    private function scheduleDelivery(IntegrationEvent $event, WebhookEndpoint $endpoint): void
    {
        // Busca mapeamento para transformar payload
        $mapping = EventMapping::forEvent($event->event_type, $endpoint->system_name);

        $payload = $mapping
            ? $mapping->transform($event->payload_json)
            : $event->payload_json;

        // Adiciona metadados
        $payload['_meta'] = [
            'event_id' => $event->id,
            'event_type' => $event->event_type,
            'source_system' => $event->source_system,
            'timestamp' => now()->toIso8601String(),
        ];

        // Cria registro de entrega
        $delivery = WebhookDelivery::createPending($endpoint->id, $event->id);

        // Despacha job de entrega
        DeliverWebhook::dispatch($delivery, $endpoint, $payload)
            ->onQueue('integrations-high');
    }

    /**
     * Trata falha no processamento.
     */
    private function handleFailure(IntegrationEvent $event, string $errorMessage): void
    {
        $retryEntry = RetryQueue::where('event_id', $event->id)->first();

        if ($retryEntry) {
            if ($retryEntry->hasExceededMaxAttempts()) {
                // Move para DLQ
                $retryEntry->moveToDeadLetter("Max retries exceeded: {$errorMessage}");

                Log::error('Event moved to DLQ', [
                    'event_id' => $event->id,
                    'attempts' => $retryEntry->attempts,
                ]);
            } else {
                // Incrementa retry
                $retryEntry->incrementAttempt();

                Log::warning('Event retry scheduled', [
                    'event_id' => $event->id,
                    'attempt' => $retryEntry->attempts,
                    'next_retry' => $retryEntry->next_retry_at,
                ]);
            }
        } else {
            // Primeira falha - adiciona a fila de retry
            RetryQueue::enqueue($event);

            Log::warning('Event added to retry queue', [
                'event_id' => $event->id,
            ]);
        }
    }

    /**
     * Reprocessa eventos da fila de retry.
     */
    public function processRetryQueue(int $limit = 100): int
    {
        $items = RetryQueue::getNextBatch($limit);
        $processed = 0;

        foreach ($items as $item) {
            $event = $item->event;

            if (!$event) {
                $item->delete();
                continue;
            }

            // Reseta status e reprocessa
            $event->update([
                'status_id' => \App\Models\Domain\DomainEventStatus::PENDING,
            ]);

            ProcessEvent::dispatch($event)->onQueue('integrations-retry');
            $processed++;
        }

        return $processed;
    }

    /**
     * Estatisticas do processador.
     */
    public function getStats(): array
    {
        return [
            'pending_events' => IntegrationEvent::pending()->count(),
            'retry_queue' => RetryQueue::count(),
            'dead_letter' => DeadLetter::count(),
            'today' => [
                'processed' => IntegrationEvent::where('status_id', 3)
                    ->whereDate('updated_at', today())
                    ->count(),
                'failed' => IntegrationEvent::where('status_id', 4)
                    ->whereDate('updated_at', today())
                    ->count(),
            ],
        ];
    }
}
