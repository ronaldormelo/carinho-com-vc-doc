<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Status de Entrega de Webhook.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainDeliveryStatus extends Model
{
    public $timestamps = false;

    protected $table = 'domain_delivery_status';

    protected $fillable = ['id', 'code', 'label'];

    public const PENDING = 1;
    public const SENT = 2;
    public const FAILED = 3;

    /**
     * Retorna o status "Pendente".
     */
    public static function pending(): self
    {
        return self::find(self::PENDING);
    }

    /**
     * Retorna o status "Enviado".
     */
    public static function sent(): self
    {
        return self::find(self::SENT);
    }

    /**
     * Retorna o status "Falhou".
     */
    public static function failed(): self
    {
        return self::find(self::FAILED);
    }
}
