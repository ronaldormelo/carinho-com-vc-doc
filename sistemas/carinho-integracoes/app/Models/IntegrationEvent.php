<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use App\Models\Domain\DomainEventStatus;

/**
 * Evento de integracao entre sistemas.
 *
 * @property int $id
 * @property string $event_type
 * @property string $source_system
 * @property array $payload_json
 * @property int $status_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class IntegrationEvent extends Model
{
    protected $table = 'integration_events';

    protected $fillable = [
        'event_type',
        'source_system',
        'payload_json',
        'status_id',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Tipos de eventos suportados
    public const TYPE_LEAD_CREATED = 'lead.created';
    public const TYPE_LEAD_UPDATED = 'lead.updated';
    public const TYPE_CLIENT_REGISTERED = 'client.registered';
    public const TYPE_CLIENT_UPDATED = 'client.updated';
    public const TYPE_SERVICE_SCHEDULED = 'service.scheduled';
    public const TYPE_SERVICE_STARTED = 'service.started';
    public const TYPE_SERVICE_COMPLETED = 'service.completed';
    public const TYPE_SERVICE_CANCELLED = 'service.cancelled';
    public const TYPE_PAYMENT_RECEIVED = 'payment.received';
    public const TYPE_PAYMENT_FAILED = 'payment.failed';
    public const TYPE_INVOICE_CREATED = 'invoice.created';
    public const TYPE_PAYOUT_PROCESSED = 'payout.processed';
    public const TYPE_WHATSAPP_INBOUND = 'whatsapp.inbound';
    public const TYPE_WHATSAPP_STATUS = 'whatsapp.status';
    public const TYPE_FEEDBACK_RECEIVED = 'feedback.received';
    public const TYPE_CAREGIVER_AVAILABLE = 'caregiver.available';
    public const TYPE_CAREGIVER_ASSIGNED = 'caregiver.assigned';

    // Sistemas de origem
    public const SOURCE_SITE = 'site';
    public const SOURCE_CRM = 'crm';
    public const SOURCE_ATENDIMENTO = 'atendimento';
    public const SOURCE_OPERACAO = 'operacao';
    public const SOURCE_FINANCEIRO = 'financeiro';
    public const SOURCE_CUIDADORES = 'cuidadores';
    public const SOURCE_MARKETING = 'marketing';
    public const SOURCE_WHATSAPP = 'whatsapp';

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainEventStatus::class, 'status_id');
    }

    /**
     * Relacionamento com entregas de webhook.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'event_id');
    }

    /**
     * Relacionamento com retry queue.
     */
    public function retryEntry(): HasOne
    {
        return $this->hasOne(RetryQueue::class, 'event_id');
    }

    /**
     * Relacionamento com dead letter.
     */
    public function deadLetter(): HasOne
    {
        return $this->hasOne(DeadLetter::class, 'event_id');
    }

    /**
     * Cria novo evento.
     */
    public static function createEvent(string $type, string $source, array $payload): self
    {
        return self::create([
            'event_type' => $type,
            'source_system' => $source,
            'payload_json' => $payload,
            'status_id' => DomainEventStatus::PENDING,
        ]);
    }

    /**
     * Marca como processando.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status_id' => DomainEventStatus::PROCESSING]);
    }

    /**
     * Marca como concluido.
     */
    public function markAsDone(): void
    {
        $this->update(['status_id' => DomainEventStatus::DONE]);
    }

    /**
     * Marca como falhou.
     */
    public function markAsFailed(): void
    {
        $this->update(['status_id' => DomainEventStatus::FAILED]);
    }

    /**
     * Verifica se esta pendente.
     */
    public function isPending(): bool
    {
        return $this->status_id === DomainEventStatus::PENDING;
    }

    /**
     * Verifica se esta processando.
     */
    public function isProcessing(): bool
    {
        return $this->status_id === DomainEventStatus::PROCESSING;
    }

    /**
     * Verifica se concluiu.
     */
    public function isDone(): bool
    {
        return $this->status_id === DomainEventStatus::DONE;
    }

    /**
     * Verifica se falhou.
     */
    public function isFailed(): bool
    {
        return $this->status_id === DomainEventStatus::FAILED;
    }

    /**
     * Obtem dado do payload.
     */
    public function getPayloadValue(string $key, $default = null)
    {
        return data_get($this->payload_json, $key, $default);
    }

    /**
     * Escopo para eventos pendentes.
     */
    public function scopePending($query)
    {
        return $query->where('status_id', DomainEventStatus::PENDING);
    }

    /**
     * Escopo para eventos por tipo.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Escopo para eventos de um sistema.
     */
    public function scopeFromSystem($query, string $system)
    {
        return $query->where('source_system', $system);
    }

    /**
     * Busca mapeamento para este evento.
     */
    public function getMapping(string $targetSystem): ?EventMapping
    {
        return EventMapping::forEvent($this->event_type, $targetSystem);
    }
}
