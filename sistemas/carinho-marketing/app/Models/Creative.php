<?php

namespace App\Models;

use App\Models\Domain\DomainCreativeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Criativo (anuncio visual/textual).
 *
 * @property int $id
 * @property int $ad_group_id
 * @property int $creative_type_id
 * @property string $headline
 * @property string $body
 * @property string|null $media_url
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Creative extends Model
{
    protected $table = 'creatives';

    protected $fillable = [
        'ad_group_id',
        'creative_type_id',
        'headline',
        'body',
        'media_url',
    ];

    protected $casts = [
        'ad_group_id' => 'integer',
        'creative_type_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com grupo de anuncios.
     */
    public function adGroup(): BelongsTo
    {
        return $this->belongsTo(AdGroup::class, 'ad_group_id');
    }

    /**
     * Relacionamento com tipo do criativo.
     */
    public function creativeType(): BelongsTo
    {
        return $this->belongsTo(DomainCreativeType::class, 'creative_type_id');
    }

    /**
     * Verifica se e uma imagem.
     */
    public function isImage(): bool
    {
        return $this->creative_type_id === DomainCreativeType::IMAGE;
    }

    /**
     * Verifica se e um video.
     */
    public function isVideo(): bool
    {
        return $this->creative_type_id === DomainCreativeType::VIDEO;
    }

    /**
     * Verifica se e apenas texto.
     */
    public function isText(): bool
    {
        return $this->creative_type_id === DomainCreativeType::TEXT;
    }

    /**
     * Verifica se requer midia.
     */
    public function requiresMedia(): bool
    {
        return in_array($this->creative_type_id, [
            DomainCreativeType::IMAGE,
            DomainCreativeType::VIDEO,
        ]);
    }

    /**
     * Verifica se o criativo esta completo.
     */
    public function isComplete(): bool
    {
        if (empty($this->headline) || empty($this->body)) {
            return false;
        }

        if ($this->requiresMedia() && empty($this->media_url)) {
            return false;
        }

        return true;
    }

    /**
     * Scope para criativos completos.
     */
    public function scopeComplete($query)
    {
        return $query->where(function ($q) {
            $q->whereNotIn('creative_type_id', [DomainCreativeType::IMAGE, DomainCreativeType::VIDEO])
                ->orWhereNotNull('media_url');
        })
            ->whereNotNull('headline')
            ->where('headline', '!=', '')
            ->whereNotNull('body')
            ->where('body', '!=', '');
    }

    /**
     * Scope por tipo.
     */
    public function scopeOfType($query, int $typeId)
    {
        return $query->where('creative_type_id', $typeId);
    }
}
