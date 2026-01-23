<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Publico-alvo do formulario.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainFormTarget extends Model
{
    protected $table = 'domain_form_target';
    public $timestamps = false;

    public const CLIENTE = 1;
    public const CUIDADOR = 2;
}
