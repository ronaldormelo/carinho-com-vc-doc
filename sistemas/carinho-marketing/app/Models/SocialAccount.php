<?php

namespace App\Models;

use App\Models\Domain\DomainChannelStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Conta em rede social.
 *
 * @property int $id
 * @property int $channel_id
 * @property string $handle
 * @property string $profile_url
 * @property int $status_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SocialAccount extends Model
{
    protected $table = 'social_accounts';

    protected $fillable = [
        'channel_id',
        'handle',
        'profile_url',
        'status_id',
    ];

    protected $casts = [
        'channel_id' => 'integer',
        'status_id' => 'integer',
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
        return $this->belongsTo(DomainChannelStatus::class, 'status_id');
    }

    /**
     * Verifica se a conta esta ativa.
     */
    public function isActive(): bool
    {
        return $this->status_id === DomainChannelStatus::ACTIVE;
    }

    /**
     * Scope para contas ativas.
     */
    public function scopeActive($query)
    {
        return $query->where('status_id', DomainChannelStatus::ACTIVE);
    }

    /**
     * Gera URL da bio com UTM.
     */
    public function getBioUrlAttribute(): string
    {
        $baseUrl = config('integrations.utm.base_url');
        $params = http_build_query([
            'utm_source' => strtolower($this->channel->name ?? 'social'),
            'utm_medium' => 'bio',
            'utm_campaign' => 'organic',
        ]);

        return "{$baseUrl}?{$params}";
    }

    /**
     * Retorna bio padrao da marca.
     */
    public function getStandardBio(): string
    {
        return config('branding.social.bio_template');
    }
}
