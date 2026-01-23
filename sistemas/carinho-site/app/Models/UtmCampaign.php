<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Campanha UTM.
 *
 * @property int $id
 * @property string $source
 * @property string $medium
 * @property string $campaign
 * @property string|null $content
 * @property string|null $term
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class UtmCampaign extends Model
{
    protected $table = 'utm_campaigns';

    protected $fillable = [
        'source',
        'medium',
        'campaign',
        'content',
        'term',
    ];

    /**
     * Relacao com submissoes.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'utm_id');
    }

    /**
     * Encontra ou cria campanha UTM.
     */
    public static function findOrCreateFromParams(array $params): ?self
    {
        if (empty($params['source']) || empty($params['medium']) || empty($params['campaign'])) {
            return null;
        }

        return static::firstOrCreate([
            'source' => $params['source'],
            'medium' => $params['medium'],
            'campaign' => $params['campaign'],
            'content' => $params['content'] ?? null,
            'term' => $params['term'] ?? null,
        ]);
    }

    /**
     * Extrai parametros UTM de request.
     */
    public static function extractFromRequest($request): array
    {
        return [
            'source' => $request->input('utm_source') ?? $request->session()->get('utm_source'),
            'medium' => $request->input('utm_medium') ?? $request->session()->get('utm_medium'),
            'campaign' => $request->input('utm_campaign') ?? $request->session()->get('utm_campaign'),
            'content' => $request->input('utm_content') ?? $request->session()->get('utm_content'),
            'term' => $request->input('utm_term') ?? $request->session()->get('utm_term'),
        ];
    }
}
