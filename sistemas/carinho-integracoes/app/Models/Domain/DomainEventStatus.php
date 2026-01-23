<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Status de Evento de Integracao.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainEventStatus extends Model
{
    public $timestamps = false;

    protected $table = 'domain_event_status';

    protected $fillable = ['id', 'code', 'label'];

    public const PENDING = 1;
    public const PROCESSING = 2;
    public const DONE = 3;
    public const FAILED = 4;

    /**
     * Retorna o status "Pendente".
     */
    public static function pending(): self
    {
        return self::find(self::PENDING);
    }

    /**
     * Retorna o status "Processando".
     */
    public static function processing(): self
    {
        return self::find(self::PROCESSING);
    }

    /**
     * Retorna o status "Concluído".
     */
    public static function done(): self
    {
        return self::find(self::DONE);
    }

    /**
     * Retorna o status "Falhou".
     */
    public static function failed(): self
    {
        return self::find(self::FAILED);
    }
}
