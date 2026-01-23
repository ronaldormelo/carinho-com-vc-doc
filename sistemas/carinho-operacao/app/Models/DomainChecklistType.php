<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de checklist (inicio ou fim).
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainChecklistType extends Model
{
    protected $table = 'domain_checklist_type';
    public $timestamps = false;

    protected $fillable = ['code', 'label'];

    public const START = 1;
    public const END = 2;
}
