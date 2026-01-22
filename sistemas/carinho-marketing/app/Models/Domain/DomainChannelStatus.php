<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Status de canais de marketing.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainChannelStatus extends Model
{
    public $timestamps = false;

    protected $table = 'domain_channel_status';

    protected $fillable = ['code', 'label'];

    public const ACTIVE = 1;
    public const INACTIVE = 2;

    /**
     * Retorna status ativo.
     */
    public static function active(): int
    {
        return self::ACTIVE;
    }

    /**
     * Retorna status inativo.
     */
    public static function inactive(): int
    {
        return self::INACTIVE;
    }

    /**
     * Verifica se o status e ativo.
     */
    public function isActive(): bool
    {
        return $this->id === self::ACTIVE;
    }
}
