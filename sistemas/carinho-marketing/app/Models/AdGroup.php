<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Grupo de anuncios dentro de uma campanha.
 *
 * @property int $id
 * @property int $campaign_id
 * @property string $name
 * @property array $targeting_json
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class AdGroup extends Model
{
    protected $table = 'ad_groups';

    protected $fillable = [
        'campaign_id',
        'name',
        'targeting_json',
    ];

    protected $casts = [
        'campaign_id' => 'integer',
        'targeting_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com campanha.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /**
     * Relacionamento com criativos.
     */
    public function creatives(): HasMany
    {
        return $this->hasMany(Creative::class, 'ad_group_id');
    }

    /**
     * Retorna configuracao de targeting de idade.
     */
    public function getAgeTargeting(): ?array
    {
        return $this->targeting_json['age'] ?? null;
    }

    /**
     * Retorna configuracao de targeting de localizacao.
     */
    public function getLocationTargeting(): ?array
    {
        return $this->targeting_json['locations'] ?? null;
    }

    /**
     * Retorna configuracao de targeting de interesses.
     */
    public function getInterestsTargeting(): ?array
    {
        return $this->targeting_json['interests'] ?? null;
    }

    /**
     * Define targeting de idade.
     */
    public function setAgeTargeting(int $minAge, int $maxAge): void
    {
        $targeting = $this->targeting_json ?? [];
        $targeting['age'] = [
            'min' => $minAge,
            'max' => $maxAge,
        ];
        $this->targeting_json = $targeting;
    }

    /**
     * Define targeting de localizacao.
     */
    public function setLocationTargeting(array $locations): void
    {
        $targeting = $this->targeting_json ?? [];
        $targeting['locations'] = $locations;
        $this->targeting_json = $targeting;
    }

    /**
     * Define targeting de interesses.
     */
    public function setInterestsTargeting(array $interests): void
    {
        $targeting = $this->targeting_json ?? [];
        $targeting['interests'] = $interests;
        $this->targeting_json = $targeting;
    }

    /**
     * Verifica se o grupo tem criativos.
     */
    public function hasCreatives(): bool
    {
        return $this->creatives()->exists();
    }
}
