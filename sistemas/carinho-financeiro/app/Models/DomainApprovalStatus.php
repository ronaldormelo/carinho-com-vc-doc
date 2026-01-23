<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de domínio para status de aprovação.
 */
class DomainApprovalStatus extends Model
{
    protected $table = 'domain_approval_status';
    public $timestamps = false;

    // Constantes de status
    public const PENDING = 1;
    public const APPROVED = 2;
    public const REJECTED = 3;
    public const AUTO_APPROVED = 4;

    /**
     * Obtém status por código.
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
