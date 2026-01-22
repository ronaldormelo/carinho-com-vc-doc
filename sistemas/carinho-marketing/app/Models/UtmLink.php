<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Link UTM para rastreamento de origem.
 *
 * @property int $id
 * @property string $source
 * @property string $medium
 * @property string $campaign
 * @property string|null $content
 * @property string|null $term
 * @property \Carbon\Carbon $created_at
 */
class UtmLink extends Model
{
    public $timestamps = false;

    protected $table = 'utm_links';

    protected $fillable = [
        'source',
        'medium',
        'campaign',
        'content',
        'term',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Boot do model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    /**
     * Relacionamento com landing pages.
     */
    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class, 'utm_default_id');
    }

    /**
     * Gera a URL completa com parametros UTM.
     */
    public function buildUrl(?string $baseUrl = null): string
    {
        $baseUrl = $baseUrl ?? config('integrations.utm.base_url');

        $params = array_filter([
            'utm_source' => $this->source,
            'utm_medium' => $this->medium,
            'utm_campaign' => $this->campaign,
            'utm_content' => $this->content,
            'utm_term' => $this->term,
        ]);

        $queryString = http_build_query($params);

        return "{$baseUrl}?{$queryString}";
    }

    /**
     * Gera URL curta para WhatsApp.
     */
    public function buildWhatsAppUrl(?string $phone = null): string
    {
        $baseUrl = 'https://wa.me';
        $phone = $phone ?? config('branding.phone');

        $utmUrl = $this->buildUrl();
        $text = urlencode(config('branding.social.cta_default') . "\n\n" . $utmUrl);

        if ($phone) {
            return "{$baseUrl}/{$phone}?text={$text}";
        }

        return "{$baseUrl}?text={$text}";
    }

    /**
     * Cria um link UTM a partir de parametros.
     */
    public static function createFromParams(
        string $source,
        string $medium,
        string $campaign,
        ?string $content = null,
        ?string $term = null
    ): self {
        return self::create([
            'source' => strtolower($source),
            'medium' => strtolower($medium),
            'campaign' => strtolower(str_replace(' ', '-', $campaign)),
            'content' => $content ? strtolower($content) : null,
            'term' => $term ? strtolower($term) : null,
        ]);
    }

    /**
     * Scope por source.
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', strtolower($source));
    }

    /**
     * Scope por medium.
     */
    public function scopeByMedium($query, string $medium)
    {
        return $query->where('medium', strtolower($medium));
    }

    /**
     * Scope por campaign.
     */
    public function scopeByCampaign($query, string $campaign)
    {
        return $query->where('campaign', strtolower($campaign));
    }

    /**
     * Retorna identificador unico do UTM.
     */
    public function getIdentifierAttribute(): string
    {
        return implode('_', array_filter([
            $this->source,
            $this->medium,
            $this->campaign,
            $this->content,
        ]));
    }
}
