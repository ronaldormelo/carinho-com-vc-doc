<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Status de solicitacao LGPD (dominio).
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainRequestStatus extends Model
{
    protected $table = 'domain_request_status';

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes
    public const OPEN = 1;
    public const IN_PROGRESS = 2;
    public const DONE = 3;
    public const REJECTED = 4;

    public const CODES = [
        self::OPEN => 'open',
        self::IN_PROGRESS => 'in_progress',
        self::DONE => 'done',
        self::REJECTED => 'rejected',
    ];

    public function dataRequests(): HasMany
    {
        return $this->hasMany(DataRequest::class, 'status_id');
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
