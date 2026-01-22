<?php

namespace App\Models;

use App\Models\Domain\DomainPageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Pagina do site.
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property int $status_id
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string|null $seo_keywords
 * @property array $content_json
 * @property \Carbon\Carbon|null $published_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class SitePage extends Model
{
    use SoftDeletes;

    protected $table = 'site_pages';

    protected $fillable = [
        'slug',
        'title',
        'status_id',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'content_json',
        'published_at',
    ];

    protected $casts = [
        'content_json' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * Relacao com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainPageStatus::class, 'status_id');
    }

    /**
     * Relacao com secoes.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class, 'page_id')->orderBy('sort_order');
    }

    /**
     * Scope para paginas publicadas.
     */
    public function scopePublished($query)
    {
        return $query->where('status_id', DomainPageStatus::PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Obtem pagina pelo slug com cache.
     */
    public static function findBySlug(string $slug): ?self
    {
        return Cache::remember(
            "page_{$slug}",
            config('site.cache.pages', 3600),
            fn () => static::with('sections')->published()->where('slug', $slug)->first()
        );
    }

    /**
     * Limpa cache da pagina.
     */
    public function clearCache(): void
    {
        Cache::forget("page_{$this->slug}");
    }

    /**
     * Retorna SEO title formatado.
     */
    public function getSeoTitleAttribute(): string
    {
        $title = $this->attributes['seo_title'] ?? $this->title;
        return $title . config('branding.seo.title_suffix');
    }
}
