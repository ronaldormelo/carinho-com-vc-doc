<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Secao de uma pagina.
 *
 * @property int $id
 * @property int $page_id
 * @property string $type
 * @property array $content_json
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class PageSection extends Model
{
    protected $table = 'page_sections';

    protected $fillable = [
        'page_id',
        'type',
        'content_json',
        'sort_order',
    ];

    protected $casts = [
        'content_json' => 'array',
    ];

    /**
     * Relacao com pagina.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(SitePage::class, 'page_id');
    }
}
