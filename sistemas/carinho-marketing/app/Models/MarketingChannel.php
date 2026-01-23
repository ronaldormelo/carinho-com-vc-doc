<?php

namespace App\Models;

use App\Models\Domain\DomainChannelStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Canal de marketing (Facebook, Instagram, Google, etc).
 *
 * @property int $id
 * @property string $name
 * @property int $status_id
 * @property DomainChannelStatus $status
 */
class MarketingChannel extends Model
{
    public $timestamps = false;

    protected $table = 'marketing_channels';

    protected $fillable = [
        'name',
        'status_id',
    ];

    protected $casts = [
        'status_id' => 'integer',
    ];

    /**
     * Relacionamento com status do canal.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainChannelStatus::class, 'status_id');
    }

    /**
     * Relacionamento com contas sociais.
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class, 'channel_id');
    }

    /**
     * Relacionamento com itens do calendario.
     */
    public function contentCalendar(): HasMany
    {
        return $this->hasMany(ContentCalendar::class, 'channel_id');
    }

    /**
     * Relacionamento com campanhas.
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'channel_id');
    }

    /**
     * Verifica se o canal esta ativo.
     */
    public function isActive(): bool
    {
        return $this->status_id === DomainChannelStatus::ACTIVE;
    }

    /**
     * Scope para canais ativos.
     */
    public function scopeActive($query)
    {
        return $query->where('status_id', DomainChannelStatus::ACTIVE);
    }

    /**
     * Retorna canais de redes sociais.
     */
    public function scopeSocial($query)
    {
        return $query->whereIn('name', ['Facebook', 'Instagram', 'LinkedIn', 'Twitter']);
    }

    /**
     * Retorna canais de anuncios.
     */
    public function scopeAds($query)
    {
        return $query->whereIn('name', ['Meta Ads', 'Google Ads']);
    }
}
