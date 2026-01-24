<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabela de domínio para status de aprovação.
 */
class DomainApprovalStatus extends Model
{
    protected $table = 'domain_approval_status';
    
    public $timestamps = false;

    protected $fillable = ['code', 'label'];

    // Constantes de status
    public const PENDING = 1;
    public const APPROVED = 2;
    public const REJECTED = 3;
}
