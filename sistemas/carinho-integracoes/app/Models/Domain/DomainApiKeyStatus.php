<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Status de API Key.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainApiKeyStatus extends Model
{
    public $timestamps = false;

    protected $table = 'domain_api_key_status';

    protected $fillable = ['id', 'code', 'label'];

    public const ACTIVE = 1;
    public const REVOKED = 2;

    /**
     * Retorna o status "Ativo".
     */
    public static function active(): self
    {
        return self::find(self::ACTIVE);
    }

    /**
     * Retorna o status "Revogado".
     */
    public static function revoked(): self
    {
        return self::find(self::REVOKED);
    }
}
