<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientEvent;
use App\Models\Domain\DomainEventType;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

/**
 * Serviço de Eventos/Timeline de Clientes
 * 
 * Gerencia o histórico cronológico padronizado de eventos do cliente.
 * Prática tradicional para manter visibilidade completa do relacionamento.
 */
class ClientEventService
{
    /**
     * Registra um novo evento na timeline
     */
    public function logEvent(
        int $clientId,
        int $eventTypeId,
        string $title,
        ?string $description = null,
        ?array $metadata = null,
        ?int $relatedId = null,
        ?string $relatedType = null,
        ?\DateTime $occurredAt = null
    ): ClientEvent {
        return ClientEvent::logEvent(
            $clientId,
            $eventTypeId,
            $title,
            $description,
            $metadata,
            $relatedId,
            $relatedType,
            $occurredAt
        );
    }

    /**
     * Obtém timeline completa do cliente
     */
    public function getTimeline(int $clientId, int $limit = 50): Collection
    {
        return ClientEvent::forClient($clientId)
            ->with('eventType')
            ->chronological()
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém timeline por categoria
     */
    public function getTimelineByCategory(int $clientId, string $category, int $limit = 20): Collection
    {
        return ClientEvent::forClient($clientId)
            ->with('eventType')
            ->ofCategory($category)
            ->chronological()
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém timeline entre datas
     */
    public function getTimelineBetweenDates(int $clientId, $startDate, $endDate): Collection
    {
        return ClientEvent::forClient($clientId)
            ->with('eventType')
            ->betweenDates($startDate, $endDate)
            ->chronological()
            ->get();
    }

    /**
     * Obtém resumo de eventos por tipo
     */
    public function getEventsSummary(int $clientId): array
    {
        $events = ClientEvent::forClient($clientId)
            ->selectRaw('event_type_id, COUNT(*) as count')
            ->groupBy('event_type_id')
            ->pluck('count', 'event_type_id')
            ->toArray();

        $eventTypes = DomainEventType::cacheAll();
        $summary = [];

        foreach ($eventTypes as $type) {
            $summary[$type->code] = [
                'label' => $type->label,
                'category' => $type->category,
                'count' => $events[$type->id] ?? 0,
            ];
        }

        return $summary;
    }

    /**
     * Obtém estatísticas de eventos
     */
    public function getStatistics($startDate = null, $endDate = null): array
    {
        $query = ClientEvent::query();

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        $total = $query->count();
        
        $byCategory = $query->with('eventType')
            ->get()
            ->groupBy(fn($e) => $e->eventType?->category)
            ->map(fn($events) => $events->count())
            ->toArray();

        $byType = $query->selectRaw('event_type_id, COUNT(*) as count')
            ->groupBy('event_type_id')
            ->pluck('count', 'event_type_id')
            ->toArray();

        return [
            'total' => $total,
            'by_category' => $byCategory,
            'by_type' => $byType,
        ];
    }

    // ==========================================
    // MÉTODOS DE CONVENIÊNCIA PARA EVENTOS COMUNS
    // ==========================================

    /**
     * Registra evento de contato
     */
    public function logContact(int $clientId, string $channel, string $summary): ClientEvent
    {
        $eventType = match (strtolower($channel)) {
            'whatsapp' => DomainEventType::CONTACT_WHATSAPP,
            'phone', 'telefone' => DomainEventType::CONTACT_PHONE,
            'email' => DomainEventType::CONTACT_EMAIL,
            default => DomainEventType::CONTACT_PHONE,
        };

        return $this->logEvent(
            $clientId,
            $eventType,
            "Contato via {$channel}",
            $summary
        );
    }

    /**
     * Registra reclamação
     */
    public function logComplaint(int $clientId, string $description, ?array $metadata = null): ClientEvent
    {
        return $this->logEvent(
            $clientId,
            DomainEventType::COMPLAINT,
            'Reclamação registrada',
            $description,
            $metadata
        );
    }

    /**
     * Registra feedback positivo
     */
    public function logPositiveFeedback(int $clientId, string $description): ClientEvent
    {
        return $this->logEvent(
            $clientId,
            DomainEventType::FEEDBACK_POSITIVE,
            'Feedback positivo',
            $description
        );
    }

    /**
     * Registra feedback negativo
     */
    public function logNegativeFeedback(int $clientId, string $description): ClientEvent
    {
        return $this->logEvent(
            $clientId,
            DomainEventType::FEEDBACK_NEGATIVE,
            'Feedback negativo',
            $description
        );
    }

    /**
     * Registra pagamento recebido
     */
    public function logPaymentReceived(int $clientId, float $value, ?string $reference = null): ClientEvent
    {
        return $this->logEvent(
            $clientId,
            DomainEventType::PAYMENT_RECEIVED,
            'Pagamento recebido',
            $reference,
            ['value' => $value]
        );
    }

    /**
     * Registra pagamento em atraso
     */
    public function logPaymentOverdue(int $clientId, float $value, int $daysOverdue): ClientEvent
    {
        return $this->logEvent(
            $clientId,
            DomainEventType::PAYMENT_OVERDUE,
            'Pagamento em atraso',
            "Valor: R$ {$value} - {$daysOverdue} dias em atraso",
            ['value' => $value, 'days_overdue' => $daysOverdue]
        );
    }

    /**
     * Busca eventos recentes por tipo
     */
    public function getRecentEventsByType(int $eventTypeId, int $days = 30, int $limit = 50): Collection
    {
        return ClientEvent::ofType($eventTypeId)
            ->recent($days)
            ->with(['client', 'eventType'])
            ->chronological()
            ->limit($limit)
            ->get();
    }

    /**
     * Conta eventos positivos vs negativos de um cliente
     */
    public function getSentimentSummary(int $clientId): array
    {
        $events = ClientEvent::forClient($clientId)->get();

        $positive = $events->filter(fn($e) => $e->isPositive())->count();
        $negative = $events->filter(fn($e) => $e->isNegative())->count();
        $neutral = $events->count() - $positive - $negative;

        return [
            'positive' => $positive,
            'negative' => $negative,
            'neutral' => $neutral,
            'total' => $events->count(),
            'sentiment_score' => $events->count() > 0 
                ? round((($positive - $negative) / $events->count()) * 100, 1)
                : 0,
        ];
    }
}
