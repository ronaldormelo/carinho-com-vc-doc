<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoria de FAQ.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $sort_order
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class FaqCategory extends Model
{
    protected $table = 'faq_categories';

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Relacao com itens.
     */
    public function items(): HasMany
    {
        return $this->hasMany(FaqItem::class, 'category_id')->orderBy('sort_order');
    }

    /**
     * Scope para categorias ativas.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true)->orderBy('sort_order');
    }
}
