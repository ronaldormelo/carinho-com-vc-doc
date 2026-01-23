<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de check (in ou out).
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainCheckType extends Model
{
    protected $table = 'domain_check_type';
    public $timestamps = false;

    protected $fillable = ['code', 'label'];

    public const IN = 1;
    public const OUT = 2;
}
