<?php

namespace App\Models;

use App\Models\Domain\DomainLandingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Landing Page de captacao.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property int $status_id
 * @property int|null $utm_default_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class LandingPage extends Model
{
    protected $table = 'landing_pages';

    protected $fillable = [
        'slug',
        'name',
        'status_id',
        'utm_default_id',
    ];

    protected $casts = [
        'status_id' => 'integer',
        'utm_default_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainLandingStatus::class, 'status_id');
    }

    /**
     * Relacionamento com UTM padrao.
     */
    public function utmDefault(): BelongsTo
    {
        return $this->belongsTo(UtmLink::class, 'utm_default_id');
    }

    /**
     * Verifica se a landing page esta publicada.
     */
    public function isPublished(): bool
    {
        return $this->status_id === DomainLandingStatus::PUBLISHED;
    }

    /**
     * Verifica se pode ser editada.
     */
    public function isEditable(): bool
    {
        return in_array($this->status_id, [
            DomainLandingStatus::DRAFT,
            DomainLandingStatus::PUBLISHED,
        ]);
    }

    /**
     * Retorna URL completa da landing page.
     */
    public function getUrlAttribute(): string
    {
        $baseUrl = config('integrations.site.base_url', 'https://carinho.com.vc');

        return rtrim($baseUrl, '/') . '/lp/' . $this->slug;
    }

    /**
     * Retorna URL com UTM padrao.
     */
    public function getUrlWithUtmAttribute(): string
    {
        if (!$this->utmDefault) {
            return $this->url;
        }

        return $this->utmDefault->buildUrl($this->url);
    }

    /**
     * Scope para landing pages publicadas.
     */
    public function scopePublished($query)
    {
        return $query->where('status_id', DomainLandingStatus::PUBLISHED);
    }

    /**
     * Scope para rascunhos.
     */
    public function scopeDraft($query)
    {
        return $query->where('status_id', DomainLandingStatus::DRAFT);
    }

    /**
     * Busca por slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)->first();
    }

    /**
     * Gera slug unico.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $slug = \Illuminate\Support\Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (self::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
