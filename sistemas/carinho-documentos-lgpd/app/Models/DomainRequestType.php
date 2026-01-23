<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipo de solicitacao LGPD (dominio).
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainRequestType extends Model
{
    protected $table = 'domain_request_type';

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes
    public const EXPORT = 1;
    public const DELETE = 2;
    public const UPDATE = 3;

    public const CODES = [
        self::EXPORT => 'export',
        self::DELETE => 'delete',
        self::UPDATE => 'update',
    ];

    public function dataRequests(): HasMany
    {
        return $this->hasMany(DataRequest::class, 'request_type_id');
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
