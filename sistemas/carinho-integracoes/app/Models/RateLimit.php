<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Controle de rate limiting por cliente.
 *
 * @property int $id
 * @property int $client_id
 * @property \Carbon\Carbon $window_start
 * @property int $count
 */
class RateLimit extends Model
{
    public $timestamps = false;

    protected $table = 'rate_limits';

    protected $fillable = [
        'client_id',
        'window_start',
        'count',
    ];

    protected $casts = [
        'window_start' => 'datetime',
    ];

    /**
     * Incrementa contador para cliente.
     */
    public static function increment(int $clientId): self
    {
        $windowStart = now()->startOfMinute();

        $record = self::firstOrCreate([
            'client_id' => $clientId,
            'window_start' => $windowStart,
        ], [
            'count' => 0,
        ]);

        $record->increment('count');

        return $record;
    }

    /**
     * Verifica se cliente excedeu limite.
     */
    public static function isExceeded(int $clientId): bool
    {
        $limit = config('integrations.rate_limit.per_minute', 60);
        $windowStart = now()->startOfMinute();

        $record = self::where('client_id', $clientId)
            ->where('window_start', $windowStart)
            ->first();

        return $record && $record->count >= $limit;
    }

    /**
     * Retorna requisicoes restantes para cliente.
     */
    public static function remaining(int $clientId): int
    {
        $limit = config('integrations.rate_limit.per_minute', 60);
        $windowStart = now()->startOfMinute();

        $record = self::where('client_id', $clientId)
            ->where('window_start', $windowStart)
            ->first();

        return max(0, $limit - ($record?->count ?? 0));
    }

    /**
     * Limpa registros antigos.
     */
    public static function cleanup(): int
    {
        return self::where('window_start', '<', now()->subHour())->delete();
    }

    /**
     * Reseta limite para cliente.
     */
    public static function reset(int $clientId): void
    {
        self::where('client_id', $clientId)->delete();
    }

    /**
     * Estatisticas de rate limiting.
     */
    public static function getStats(): array
    {
        $windowStart = now()->startOfMinute();

        return [
            'active_clients' => self::where('window_start', $windowStart)->count(),
            'total_requests' => self::where('window_start', $windowStart)->sum('count'),
            'exceeded_clients' => self::where('window_start', $windowStart)
                ->where('count', '>=', config('integrations.rate_limit.per_minute', 60))
                ->count(),
        ];
    }
}
