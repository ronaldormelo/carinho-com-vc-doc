<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Fila de retry para eventos que falharam.
 *
 * @property int $id
 * @property int $event_id
 * @property \Carbon\Carbon $next_retry_at
 * @property int $attempts
 */
class RetryQueue extends Model
{
    public $timestamps = false;

    protected $table = 'retry_queue';

    protected $fillable = [
        'event_id',
        'next_retry_at',
        'attempts',
    ];

    protected $casts = [
        'next_retry_at' => 'datetime',
    ];

    /**
     * Relacionamento com evento.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(IntegrationEvent::class, 'event_id');
    }

    /**
     * Adiciona evento a fila de retry.
     */
    public static function enqueue(IntegrationEvent $event): self
    {
        $existing = self::where('event_id', $event->id)->first();

        if ($existing) {
            $existing->incrementAttempt();
            return $existing;
        }

        return self::create([
            'event_id' => $event->id,
            'next_retry_at' => self::calculateNextRetry(0),
            'attempts' => 1,
        ]);
    }

    /**
     * Incrementa tentativa e recalcula proximo retry.
     */
    public function incrementAttempt(): void
    {
        $this->update([
            'attempts' => $this->attempts + 1,
            'next_retry_at' => self::calculateNextRetry($this->attempts),
        ]);
    }

    /**
     * Calcula proximo tempo de retry com backoff exponencial.
     */
    public static function calculateNextRetry(int $currentAttempts): Carbon
    {
        $baseDelay = config('integrations.retry.base_delay', 60);
        $multiplier = config('integrations.retry.backoff_multiplier', 2);

        $delaySeconds = $baseDelay * pow($multiplier, $currentAttempts);

        // Limita a 1 hora
        $delaySeconds = min($delaySeconds, 3600);

        return now()->addSeconds($delaySeconds);
    }

    /**
     * Verifica se excedeu limite de tentativas.
     */
    public function hasExceededMaxAttempts(): bool
    {
        $maxAttempts = config('integrations.retry.max_attempts', 5);

        return $this->attempts >= $maxAttempts;
    }

    /**
     * Remove da fila.
     */
    public function dequeue(): void
    {
        $this->delete();
    }

    /**
     * Move para DLQ se excedeu tentativas.
     */
    public function moveToDeadLetter(string $reason): void
    {
        DeadLetter::create([
            'event_id' => $this->event_id,
            'reason' => $reason,
            'created_at' => now(),
        ]);

        $this->event->markAsFailed();
        $this->delete();
    }

    /**
     * Escopo para items prontos para retry.
     */
    public function scopeReadyForRetry($query)
    {
        return $query->where('next_retry_at', '<=', now());
    }

    /**
     * Escopo ordenado por proximo retry.
     */
    public function scopeOrderByNextRetry($query)
    {
        return $query->orderBy('next_retry_at');
    }

    /**
     * Busca proximos eventos para retry.
     */
    public static function getNextBatch(int $limit = 100)
    {
        return self::readyForRetry()
            ->orderByNextRetry()
            ->limit($limit)
            ->with('event')
            ->get();
    }
}
