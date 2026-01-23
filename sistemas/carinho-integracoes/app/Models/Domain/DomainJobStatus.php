<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Status de Job de Sincronizacao.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainJobStatus extends Model
{
    public $timestamps = false;

    protected $table = 'domain_job_status';

    protected $fillable = ['id', 'code', 'label'];

    public const QUEUED = 1;
    public const RUNNING = 2;
    public const DONE = 3;
    public const FAILED = 4;

    /**
     * Retorna o status "Na Fila".
     */
    public static function queued(): self
    {
        return self::find(self::QUEUED);
    }

    /**
     * Retorna o status "Executando".
     */
    public static function running(): self
    {
        return self::find(self::RUNNING);
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
