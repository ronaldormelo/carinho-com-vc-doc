<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Configuracao do site.
 *
 * @property int $id
 * @property string $setting_key
 * @property string $setting_value
 * @property string|null $description
 * @property \Carbon\Carbon|null $updated_at
 */
class SiteSetting extends Model
{
    protected $table = 'site_settings';
    public $timestamps = false;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'description',
    ];

    /**
     * Boot do model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->updated_at = now();
            Cache::forget("site_setting_{$model->setting_key}");
        });
    }

    /**
     * Obtem valor de configuracao.
     */
    public static function getValue(string $key, $default = null)
    {
        return Cache::remember(
            "site_setting_{$key}",
            config('site.cache.settings', 86400),
            fn () => static::where('setting_key', $key)->value('setting_value') ?? $default
        );
    }

    /**
     * Define valor de configuracao.
     */
    public static function setValue(string $key, $value, ?string $description = null): void
    {
        static::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $value,
                'description' => $description,
            ]
        );
    }
}
