<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipo de acao de acesso (dominio).
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainAccessAction extends Model
{
    protected $table = 'domain_access_action';

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes
    public const VIEW = 1;
    public const DOWNLOAD = 2;
    public const SIGN = 3;
    public const DELETE = 4;

    public const CODES = [
        self::VIEW => 'view',
        self::DOWNLOAD => 'download',
        self::SIGN => 'sign',
        self::DELETE => 'delete',
    ];

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class, 'action_id');
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
