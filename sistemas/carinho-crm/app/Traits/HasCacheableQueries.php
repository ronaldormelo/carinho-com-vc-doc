<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Trait para queries com cache (performance)
 */
trait HasCacheableQueries
{
    /**
     * Obter todos os registros com cache
     */
    public static function allCached(int $ttl = null): \Illuminate\Database\Eloquent\Collection
    {
        $ttl = $ttl ?? config('cache.ttl.domains', 3600);
        $cacheKey = static::getCacheKey('all');

        return Cache::remember($cacheKey, $ttl, function () {
            return static::all();
        });
    }

    /**
     * Encontrar por ID com cache
     */
    public static function findCached(int $id, int $ttl = null): ?static
    {
        $ttl = $ttl ?? config('cache.ttl.domains', 3600);
        $cacheKey = static::getCacheKey("id:{$id}");

        return Cache::remember($cacheKey, $ttl, function () use ($id) {
            return static::find($id);
        });
    }

    /**
     * Encontrar por código com cache
     */
    public static function findByCodeCached(string $code, int $ttl = null): ?static
    {
        $ttl = $ttl ?? config('cache.ttl.domains', 3600);
        $cacheKey = static::getCacheKey("code:{$code}");

        return Cache::remember($cacheKey, $ttl, function () use ($code) {
            return static::where('code', $code)->first();
        });
    }

    /**
     * Gerar chave de cache para o modelo
     */
    protected static function getCacheKey(string $suffix): string
    {
        $table = (new static)->getTable();
        return "model:{$table}:{$suffix}";
    }

    /**
     * Limpar cache do modelo
     */
    public static function clearCache(): void
    {
        $table = (new static)->getTable();
        Cache::forget("model:{$table}:all");
    }

    /**
     * Boot do trait - limpar cache ao modificar
     */
    public static function bootHasCacheableQueries(): void
    {
        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }
}
