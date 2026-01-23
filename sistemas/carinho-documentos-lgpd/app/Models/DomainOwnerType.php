<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipo de proprietario de documento (dominio).
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainOwnerType extends Model
{
    protected $table = 'domain_owner_type';

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes
    public const CLIENT = 1;
    public const CAREGIVER = 2;
    public const COMPANY = 3;

    public const CODES = [
        self::CLIENT => 'client',
        self::CAREGIVER => 'caregiver',
        self::COMPANY => 'company',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'owner_type_id');
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
