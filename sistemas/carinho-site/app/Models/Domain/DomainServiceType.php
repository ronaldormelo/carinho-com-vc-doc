<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de servico.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 * @property string|null $description
 */
class DomainServiceType extends Model
{
    protected $table = 'domain_service_type';
    public $timestamps = false;

    public const HORISTA = 1;
    public const DIARIO = 2;
    public const MENSAL = 3;
}
