<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabela de domínio para tipos de parceria.
 */
class DomainPartnershipType extends Model
{
    protected $table = 'domain_partnership_type';
    
    public $timestamps = false;

    protected $fillable = ['code', 'label'];

    // Constantes de tipo
    public const CLINIC = 1;
    public const HOSPITAL = 2;
    public const CAREGIVER = 3;
    public const CONDOMINIUM = 4;
    public const PHARMACY = 5;
    public const OTHER = 6;
}
