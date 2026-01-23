<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dead Letter Queue - eventos que falharam apos todas tentativas.
 *
 * @property int $id
 * @property int $event_id
 * @property string $reason
 * @property \Carbon\Carbon $created_at
 */
class DeadLetter extends Model
{
    public $timestamps = false;

    protected $table = 'dead_letter';

    protected $fillable = [
        'event_id',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relacionamento com evento.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(IntegrationEvent::class, 'event_id');
    }

    /**
     * Cria registro na DLQ.
     */
    public static function createFromEvent(IntegrationEvent $event, string $reason): self
    {
        $event->markAsFailed();

        return self::create([
            'event_id' => $event->id,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    /**
     * Tenta reprocessar evento.
     */
    public function retry(): bool
    {
        $event = $this->event;

        if (!$event) {
            return false;
        }

        // Reseta status do evento
        $event->update([
            'status_id' => \App\Models\Domain\DomainEventStatus::PENDING,
        ]);

        // Remove da DLQ
        $this->delete();

        return true;
    }

    /**
     * Arquiva o evento (mantém registro mas não reprocessa).
     */
    public function archive(): void
    {
        $this->update([
            'reason' => $this->reason . ' [ARCHIVED: ' . now()->toIso8601String() . ']',
        ]);
    }

    /**
     * Escopo para eventos recentes.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Escopo para eventos por tipo.
     */
    public function scopeByEventType($query, string $type)
    {
        return $query->whereHas('event', function ($q) use ($type) {
            $q->where('event_type', $type);
        });
    }

    /**
     * Estatisticas da DLQ.
     */
    public static function getStats(): array
    {
        return [
            'total' => self::count(),
            'last_24h' => self::where('created_at', '>=', now()->subDay())->count(),
            'last_7d' => self::recent(7)->count(),
            'by_type' => self::getStatsByEventType(),
        ];
    }

    /**
     * Estatísticas agrupadas por tipo de evento.
     */
    public static function getStatsByEventType(): array
    {
        return self::join('integration_events', 'dead_letter.event_id', '=', 'integration_events.id')
            ->selectRaw('integration_events.event_type, COUNT(*) as count')
            ->groupBy('integration_events.event_type')
            ->pluck('count', 'event_type')
            ->toArray();
    }

    /**
     * Verifica se item está arquivado.
     */
    public function isArchived(): bool
    {
        return str_contains($this->reason, '[ARCHIVED:');
    }

    /**
     * Escopo para itens não arquivados.
     */
    public function scopeNotArchived($query)
    {
        return $query->where('reason', 'NOT LIKE', '%[ARCHIVED:%');
    }

    /**
     * Escopo para itens arquivados.
     */
    public function scopeArchived($query)
    {
        return $query->where('reason', 'LIKE', '%[ARCHIVED:%');
    }
}
