<?php

namespace App\Models;

use App\Models\Domain\DomainContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Calendario editorial - item de conteudo.
 *
 * @property int $id
 * @property int $channel_id
 * @property string $title
 * @property \Carbon\Carbon|null $scheduled_at
 * @property int $status_id
 * @property int|null $owner_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ContentCalendar extends Model
{
    protected $table = 'content_calendar';

    protected $fillable = [
        'channel_id',
        'title',
        'scheduled_at',
        'status_id',
        'owner_id',
    ];

    protected $casts = [
        'channel_id' => 'integer',
        'status_id' => 'integer',
        'owner_id' => 'integer',
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com canal de marketing.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class, 'channel_id');
    }

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainContentStatus::class, 'status_id');
    }

    /**
     * Relacionamento com assets de conteudo.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(ContentAsset::class, 'calendar_id');
    }

    /**
     * Verifica se esta agendado.
     */
    public function isScheduled(): bool
    {
        return $this->status_id === DomainContentStatus::SCHEDULED;
    }

    /**
     * Verifica se foi publicado.
     */
    public function isPublished(): bool
    {
        return $this->status_id === DomainContentStatus::PUBLISHED;
    }

    /**
     * Verifica se pode ser editado.
     */
    public function isEditable(): bool
    {
        return in_array($this->status_id, [
            DomainContentStatus::DRAFT,
            DomainContentStatus::SCHEDULED,
        ]);
    }

    /**
     * Verifica se esta pronto para publicacao.
     */
    public function isReadyToPublish(): bool
    {
        return $this->status_id === DomainContentStatus::SCHEDULED
            && $this->scheduled_at
            && $this->scheduled_at->isPast()
            && $this->assets()->exists();
    }

    /**
     * Scope para rascunhos.
     */
    public function scopeDraft($query)
    {
        return $query->where('status_id', DomainContentStatus::DRAFT);
    }

    /**
     * Scope para agendados.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status_id', DomainContentStatus::SCHEDULED);
    }

    /**
     * Scope para publicados.
     */
    public function scopePublished($query)
    {
        return $query->where('status_id', DomainContentStatus::PUBLISHED);
    }

    /**
     * Scope para conteudo pendente de publicacao.
     */
    public function scopePendingPublication($query)
    {
        return $query
            ->where('status_id', DomainContentStatus::SCHEDULED)
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope para filtrar por periodo.
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('scheduled_at', [$startDate, $endDate]);
    }

    /**
     * Scope para esta semana.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('scheduled_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }
}
