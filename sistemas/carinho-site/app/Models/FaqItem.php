<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item de FAQ (pergunta e resposta).
 *
 * @property int $id
 * @property int $category_id
 * @property string $question
 * @property string $answer
 * @property int $sort_order
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class FaqItem extends Model
{
    protected $table = 'faq_items';

    protected $fillable = [
        'category_id',
        'question',
        'answer',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Relacao com categoria.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'category_id');
    }

    /**
     * Scope para itens ativos.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
