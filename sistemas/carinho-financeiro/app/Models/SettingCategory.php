<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SettingCategory extends Model
{
    public $timestamps = false;

    protected $table = 'setting_categories';

    protected $fillable = [
        'code',
        'name',
        'description',
        'display_order',
    ];

    // IDs das categorias
    public const PAYMENT = 1;
    public const CANCELLATION = 2;
    public const COMMISSION = 3;
    public const PRICING = 4;
    public const MARGIN = 5;
    public const PAYOUT = 6;
    public const FISCAL = 7;
    public const LIMITS = 8;
    public const BONUS = 9;

    /**
     * Configurações desta categoria.
     */
    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class, 'category_id')
            ->orderBy('display_order');
    }

    /**
     * Busca categoria por código.
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Scope ordenado por display_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}
