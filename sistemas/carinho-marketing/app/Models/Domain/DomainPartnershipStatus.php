<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabela de domínio para status de parceria.
 */
class DomainPartnershipStatus extends Model
{
    protected $table = 'domain_partnership_status';
    
    public $timestamps = false;

    protected $fillable = ['code', 'label'];

    // Constantes de status
    public const ACTIVE = 1;
    public const INACTIVE = 2;
    public const PENDING = 3;
}
