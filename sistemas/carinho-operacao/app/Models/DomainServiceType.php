<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de servico (horista, diario, mensal).
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainServiceType extends Model
{
    protected $table = 'domain_service_type';
    public $timestamps = false;

    protected $fillable = ['code', 'label'];

    public const HORISTA = 1;
    public const DIARIO = 2;
    public const MENSAL = 3;
}
