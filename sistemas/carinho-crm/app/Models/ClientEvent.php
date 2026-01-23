<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Domain\DomainEventType;

/**
 * Histórico de eventos do cliente (Timeline estruturada)
 * 
 * Registra todos os eventos relevantes na jornada do cliente de forma padronizada,
 * permitindo consultas e relatórios consistentes.
 */
class ClientEvent extends Model
{
    use HasFactory;

    protected $table = 'client_events';

    protected $fillable = [
        'client_id',
        'event_type_id',
        'title',
        'description',
        'metadata',
        'related_id',
        'related_type',
        'created_by',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function eventType()
    {
        return $this->belongsTo(DomainEventType::class, 'event_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relacionamento polimórfico com entidade relacionada
     */
    public function related()
    {
        if (!$this->related_type || !$this->related_id) {
            return null;
        }

        $modelClass = match ($this->related_type) {
            'deal' => Deal::class,
            'contract' => Contract::class,
            'proposal' => Proposal::class,
            'interaction' => Interaction::class,
            'task' => Task::class,
            default => null,
        };

        if (!$modelClass) {
            return null;
        }

        return $modelClass::find($this->related_id);
    }

    // Scopes
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeOfType($query, int $eventTypeId)
    {
        return $query->where('event_type_id', $eventTypeId);
    }

    public function scopeOfCategory($query, string $category)
    {
        return $query->whereHas('eventType', fn($q) => $q->where('category', $category));
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    public function scopeChronological($query, string $direction = 'desc')
    {
        return $query->orderBy('occurred_at', $direction);
    }

    // Métodos estáticos para criação de eventos
    public static function logEvent(
        int $clientId,
        int $eventTypeId,
        string $title,
        ?string $description = null,
        ?array $metadata = null,
        ?int $relatedId = null,
        ?string $relatedType = null,
        ?\DateTime $occurredAt = null
    ): self {
        return self::create([
            'client_id' => $clientId,
            'event_type_id' => $eventTypeId,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata,
            'related_id' => $relatedId,
            'related_type' => $relatedType,
            'created_by' => auth()->id(),
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    // Métodos de conveniência para eventos comuns
    public static function logContractSigned(Client $client, Contract $contract): self
    {
        return self::logEvent(
            $client->id,
            DomainEventType::CONTRACT_SIGNED,
            'Contrato assinado',
            "Contrato #{$contract->id} assinado digitalmente",
            ['contract_value' => $contract->proposal?->price],
            $contract->id,
            'contract',
            $contract->signed_at
        );
    }

    public static function logReviewCompleted(Client $client, ClientReview $review): self
    {
        return self::logEvent(
            $client->id,
            DomainEventType::REVIEW_COMPLETED,
            'Revisão periódica realizada',
            $review->observations,
            [
                'satisfaction_score' => $review->satisfaction_score,
                'renewal_intent' => $review->contract_renewal_intent,
            ],
            $review->id,
            'review'
        );
    }

    public static function logReferralMade(Client $client, ClientReferral $referral): self
    {
        return self::logEvent(
            $client->id,
            DomainEventType::REFERRAL_MADE,
            'Indicação realizada',
            "Cliente indicou: {$referral->referred_name}",
            ['referral_id' => $referral->id],
            $referral->id,
            'referral'
        );
    }

    /**
     * Obter categoria do evento
     */
    public function getCategoryAttribute(): ?string
    {
        return $this->eventType?->category;
    }

    /**
     * Verificar se é evento positivo
     */
    public function isPositive(): bool
    {
        return in_array($this->event_type_id, [
            DomainEventType::PROPOSAL_ACCEPTED,
            DomainEventType::DEAL_WON,
            DomainEventType::CONTRACT_SIGNED,
            DomainEventType::CONTRACT_RENEWED,
            DomainEventType::PAYMENT_RECEIVED,
            DomainEventType::FEEDBACK_POSITIVE,
            DomainEventType::REFERRAL_MADE,
        ]);
    }

    /**
     * Verificar se é evento negativo
     */
    public function isNegative(): bool
    {
        return in_array($this->event_type_id, [
            DomainEventType::PROPOSAL_REJECTED,
            DomainEventType::DEAL_LOST,
            DomainEventType::CONTRACT_CLOSED,
            DomainEventType::PAYMENT_OVERDUE,
            DomainEventType::COMPLAINT,
            DomainEventType::FEEDBACK_NEGATIVE,
        ]);
    }
}
