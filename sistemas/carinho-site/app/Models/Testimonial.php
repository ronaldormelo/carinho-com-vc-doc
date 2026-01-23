<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Depoimento de cliente.
 *
 * @property int $id
 * @property string $name
 * @property string|null $role
 * @property string $content
 * @property int $rating
 * @property string|null $avatar_url
 * @property bool $featured
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Testimonial extends Model
{
    protected $table = 'testimonials';

    protected $fillable = [
        'name',
        'role',
        'content',
        'rating',
        'avatar_url',
        'featured',
        'active',
    ];

    protected $casts = [
        'rating' => 'integer',
        'featured' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Scope para depoimentos ativos.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para depoimentos em destaque.
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
}
