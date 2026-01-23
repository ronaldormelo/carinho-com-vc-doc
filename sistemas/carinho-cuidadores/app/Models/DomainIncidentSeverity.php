<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabela de domínio para severidade de ocorrências.
 */
class DomainIncidentSeverity extends Model
{
    protected $table = 'domain_incident_severity';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'code',
        'label',
        'weight',
    ];

    protected $casts = [
        'weight' => 'integer',
    ];

    /**
     * Constantes de ID para uso direto.
     */
    public const LOW = 1;
    public const MEDIUM = 2;
    public const HIGH = 3;
    public const CRITICAL = 4;

    /**
     * Retorna severidade por código.
     */
    public static function byCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }

    /**
     * Retorna severidade baixa.
     */
    public static function low(): self
    {
        return self::find(self::LOW);
    }

    /**
     * Retorna severidade média.
     */
    public static function medium(): self
    {
        return self::find(self::MEDIUM);
    }

    /**
     * Retorna severidade alta.
     */
    public static function high(): self
    {
        return self::find(self::HIGH);
    }

    /**
     * Retorna severidade crítica.
     */
    public static function critical(): self
    {
        return self::find(self::CRITICAL);
    }

    /**
     * Verifica se é severidade grave (high ou critical).
     */
    public function isSevere(): bool
    {
        return $this->id >= self::HIGH;
    }
}
