<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Status de Endpoint de Webhook.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainEndpointStatus extends Model
{
    public $timestamps = false;

    protected $table = 'domain_endpoint_status';

    protected $fillable = ['id', 'code', 'label'];

    public const ACTIVE = 1;
    public const INACTIVE = 2;

    /**
     * Retorna o status "Ativo".
     */
    public static function active(): self
    {
        return self::find(self::ACTIVE);
    }

    /**
     * Retorna o status "Inativo".
     */
    public static function inactive(): self
    {
        return self::find(self::INACTIVE);
    }
}
