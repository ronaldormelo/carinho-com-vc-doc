<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Status de pagina do site.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainPageStatus extends Model
{
    protected $table = 'domain_page_status';
    public $timestamps = false;

    public const DRAFT = 1;
    public const PUBLISHED = 2;
    public const ARCHIVED = 3;
}
