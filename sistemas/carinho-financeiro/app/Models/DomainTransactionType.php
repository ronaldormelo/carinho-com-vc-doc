<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de domínio para tipos de transação financeira.
 */
class DomainTransactionType extends Model
{
    protected $table = 'domain_transaction_type';
    public $timestamps = false;

    // Constantes de tipos
    public const RECEIPT = 1;
    public const PAYMENT = 2;
    public const TRANSFER = 3;
    public const ADJUSTMENT = 4;
    public const FEE = 5;
    public const REFUND = 6;

    /**
     * Obtém tipo por código.
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
