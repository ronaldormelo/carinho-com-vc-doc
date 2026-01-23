<?php

namespace App\Models;

use App\Models\Domain\DomainCampaignStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Campanha de marketing/anuncios.
 *
 * @property int $id
 * @property int $channel_id
 * @property string $name
 * @property string $objective
 * @property float $budget
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property int $status_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Campaign extends Model
{
    protected $table = 'campaigns';

    protected $fillable = [
        'channel_id',
        'name',
        'objective',
        'budget',
        'start_date',
        'end_date',
        'status_id',
    ];

    protected $casts = [
        'channel_id' => 'integer',
        'budget' => 'decimal:2',
        'status_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
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
        return $this->belongsTo(DomainCampaignStatus::class, 'status_id');
    }

    /**
     * Relacionamento com grupos de anuncios.
     */
    public function adGroups(): HasMany
    {
        return $this->hasMany(AdGroup::class, 'campaign_id');
    }

    /**
     * Relacionamento com metricas.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(CampaignMetric::class, 'campaign_id');
    }

    /**
     * Verifica se a campanha esta ativa.
     */
    public function isActive(): bool
    {
        return $this->status_id === DomainCampaignStatus::ACTIVE;
    }

    /**
     * Verifica se a campanha pode ser ativada.
     */
    public function canBeActivated(): bool
    {
        return in_array($this->status_id, [
            DomainCampaignStatus::PLANNED,
            DomainCampaignStatus::PAUSED,
        ]);
    }

    /**
     * Verifica se a campanha esta dentro do periodo.
     */
    public function isWithinPeriod(): bool
    {
        $today = now()->toDateString();

        if ($this->start_date && $today < $this->start_date->toDateString()) {
            return false;
        }

        if ($this->end_date && $today > $this->end_date->toDateString()) {
            return false;
        }

        return true;
    }

    /**
     * Retorna o total gasto na campanha.
     */
    public function getTotalSpendAttribute(): float
    {
        return $this->metrics()->sum('spend');
    }

    /**
     * Retorna total de leads gerados.
     */
    public function getTotalLeadsAttribute(): int
    {
        return (int) $this->metrics()->sum('leads');
    }

    /**
     * Calcula CPL (Custo por Lead).
     */
    public function getCplAttribute(): ?float
    {
        $leads = $this->getTotalLeadsAttribute();

        if ($leads === 0) {
            return null;
        }

        return $this->getTotalSpendAttribute() / $leads;
    }

    /**
     * Scope para campanhas ativas.
     */
    public function scopeActive($query)
    {
        return $query->where('status_id', DomainCampaignStatus::ACTIVE);
    }

    /**
     * Scope para campanhas em execucao.
     */
    public function scopeRunning($query)
    {
        return $query->whereIn('status_id', [
            DomainCampaignStatus::ACTIVE,
            DomainCampaignStatus::PAUSED,
        ]);
    }

    /**
     * Scope por canal.
     */
    public function scopeByChannel($query, int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    /**
     * Scope por periodo.
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->where('start_date', '<=', $endDate)
                ->where(function ($q2) use ($startDate) {
                    $q2->whereNull('end_date')
                        ->orWhere('end_date', '>=', $startDate);
                });
        });
    }
}
