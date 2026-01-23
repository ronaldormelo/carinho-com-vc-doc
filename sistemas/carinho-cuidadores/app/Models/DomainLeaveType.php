<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabela de domínio para tipos de afastamento.
 */
class DomainLeaveType extends Model
{
    protected $table = 'domain_leave_type';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'code',
        'label',
    ];

    /**
     * Constantes de ID para uso direto.
     */
    public const MEDICAL = 1;
    public const VACATION = 2;
    public const PERSONAL = 3;
    public const MATERNITY = 4;
    public const OTHER = 5;

    /**
     * Retorna tipo por código.
     */
    public static function byCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }

    /**
     * Retorna tipo de atestado médico.
     */
    public static function medical(): self
    {
        return self::find(self::MEDICAL);
    }

    /**
     * Retorna tipo de férias.
     */
    public static function vacation(): self
    {
        return self::find(self::VACATION);
    }

    /**
     * Verifica se requer documento comprobatório.
     */
    public function requiresDocument(): bool
    {
        return in_array($this->id, [self::MEDICAL, self::MATERNITY]);
    }
}
