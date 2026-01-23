<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de domínio para status de contas a pagar.
 */
class DomainPayableStatus extends Model
{
    protected $table = 'domain_payable_status';
    public $timestamps = false;

    // Constantes de status
    public const OPEN = 1;
    public const SCHEDULED = 2;
    public const PAID = 3;
    public const CANCELED = 4;

    /**
     * Obtém status por código.
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
