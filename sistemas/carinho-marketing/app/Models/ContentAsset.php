<?php

namespace App\Models;

use App\Models\Domain\DomainAssetStatus;
use App\Models\Domain\DomainCreativeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asset de conteudo (imagem, video, texto).
 *
 * @property int $id
 * @property int $calendar_id
 * @property int $asset_type_id
 * @property string $asset_url
 * @property string|null $caption
 * @property int $status_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ContentAsset extends Model
{
    protected $table = 'content_assets';

    protected $fillable = [
        'calendar_id',
        'asset_type_id',
        'asset_url',
        'caption',
        'status_id',
    ];

    protected $casts = [
        'calendar_id' => 'integer',
        'asset_type_id' => 'integer',
        'status_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com item do calendario.
     */
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(ContentCalendar::class, 'calendar_id');
    }

    /**
     * Relacionamento com tipo do asset.
     */
    public function assetType(): BelongsTo
    {
        return $this->belongsTo(DomainCreativeType::class, 'asset_type_id');
    }

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainAssetStatus::class, 'status_id');
    }

    /**
     * Verifica se e uma imagem.
     */
    public function isImage(): bool
    {
        return $this->asset_type_id === DomainCreativeType::IMAGE;
    }

    /**
     * Verifica se e um video.
     */
    public function isVideo(): bool
    {
        return $this->asset_type_id === DomainCreativeType::VIDEO;
    }

    /**
     * Verifica se esta aprovado.
     */
    public function isApproved(): bool
    {
        return in_array($this->status_id, [
            DomainAssetStatus::APPROVED,
            DomainAssetStatus::PUBLISHED,
        ]);
    }

    /**
     * Scope para assets aprovados.
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('status_id', [
            DomainAssetStatus::APPROVED,
            DomainAssetStatus::PUBLISHED,
        ]);
    }

    /**
     * Scope para imagens.
     */
    public function scopeImages($query)
    {
        return $query->where('asset_type_id', DomainCreativeType::IMAGE);
    }

    /**
     * Scope para videos.
     */
    public function scopeVideos($query)
    {
        return $query->where('asset_type_id', DomainCreativeType::VIDEO);
    }
}
