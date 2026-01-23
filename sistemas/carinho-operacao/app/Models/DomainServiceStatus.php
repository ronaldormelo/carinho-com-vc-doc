<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Status da solicitacao de servico.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainServiceStatus extends Model
{
    protected $table = 'domain_service_status';
    public $timestamps = false;

    protected $fillable = ['code', 'label'];

    public const OPEN = 1;
    public const SCHEDULED = 2;
    public const ACTIVE = 3;
    public const COMPLETED = 4;
    public const CANCELED = 5;
}
