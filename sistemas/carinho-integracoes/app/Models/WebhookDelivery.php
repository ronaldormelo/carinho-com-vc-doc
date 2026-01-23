<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Domain\DomainDeliveryStatus;

/**
 * Registro de entrega de webhook.
 *
 * @property int $id
 * @property int $endpoint_id
 * @property int $event_id
 * @property int $status_id
 * @property int $attempts
 * @property \Carbon\Carbon|null $last_attempt_at
 * @property int|null $response_code
 */
class WebhookDelivery extends Model
{
    public $timestamps = false;

    protected $table = 'webhook_deliveries';

    protected $fillable = [
        'endpoint_id',
        'event_id',
        'status_id',
        'attempts',
        'last_attempt_at',
        'response_code',
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
    ];

    /**
     * Relacionamento com endpoint.
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'endpoint_id');
    }

    /**
     * Relacionamento com evento.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(IntegrationEvent::class, 'event_id');
    }

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainDeliveryStatus::class, 'status_id');
    }

    /**
     * Cria nova entrega pendente.
     */
    public static function createPending(int $endpointId, int $eventId): self
    {
        return self::create([
            'endpoint_id' => $endpointId,
            'event_id' => $eventId,
            'status_id' => DomainDeliveryStatus::PENDING,
            'attempts' => 0,
        ]);
    }

    /**
     * Registra tentativa de entrega.
     */
    public function recordAttempt(int $responseCode, bool $success): void
    {
        $this->update([
            'attempts' => $this->attempts + 1,
            'last_attempt_at' => now(),
            'response_code' => $responseCode,
            'status_id' => $success
                ? DomainDeliveryStatus::SENT
                : ($this->shouldRetry() ? DomainDeliveryStatus::PENDING : DomainDeliveryStatus::FAILED),
        ]);
    }

    /**
     * Marca como enviado com sucesso.
     */
    public function markAsSent(int $responseCode = 200): void
    {
        $this->update([
            'status_id' => DomainDeliveryStatus::SENT,
            'attempts' => $this->attempts + 1,
            'last_attempt_at' => now(),
            'response_code' => $responseCode,
        ]);
    }

    /**
     * Marca como falhou.
     */
    public function markAsFailed(int $responseCode = 0): void
    {
        $this->update([
            'status_id' => DomainDeliveryStatus::FAILED,
            'attempts' => $this->attempts + 1,
            'last_attempt_at' => now(),
            'response_code' => $responseCode,
        ]);
    }

    /**
     * Verifica se deve tentar novamente.
     */
    public function shouldRetry(): bool
    {
        $maxAttempts = config('integrations.retry.max_attempts', 5);

        return $this->attempts < $maxAttempts;
    }

    /**
     * Calcula proximo tempo de retry com backoff exponencial.
     */
    public function getNextRetryDelay(): int
    {
        $baseDelay = config('integrations.retry.base_delay', 60);
        $multiplier = config('integrations.retry.backoff_multiplier', 2);

        return $baseDelay * pow($multiplier, $this->attempts);
    }

    /**
     * Verifica se esta pendente.
     */
    public function isPending(): bool
    {
        return $this->status_id === DomainDeliveryStatus::PENDING;
    }

    /**
     * Verifica se foi enviado.
     */
    public function isSent(): bool
    {
        return $this->status_id === DomainDeliveryStatus::SENT;
    }

    /**
     * Verifica se falhou.
     */
    public function isFailed(): bool
    {
        return $this->status_id === DomainDeliveryStatus::FAILED;
    }

    /**
     * Escopo para entregas pendentes.
     */
    public function scopePending($query)
    {
        return $query->where('status_id', DomainDeliveryStatus::PENDING);
    }

    /**
     * Escopo para entregas que falharam.
     */
    public function scopeFailed($query)
    {
        return $query->where('status_id', DomainDeliveryStatus::FAILED);
    }
}
