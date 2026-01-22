<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Status da alocacao do cuidador.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainAssignmentStatus extends Model
{
    protected $table = 'domain_assignment_status';
    public $timestamps = false;

    protected $fillable = ['code', 'label'];

    public const ASSIGNED = 1;
    public const CONFIRMED = 2;
    public const REPLACED = 3;
    public const CANCELED = 4;
}
