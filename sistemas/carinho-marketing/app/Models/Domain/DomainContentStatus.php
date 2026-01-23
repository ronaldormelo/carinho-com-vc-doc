<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Status de conteudo no calendario editorial.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainContentStatus extends Model
{
    public $timestamps = false;

    protected $table = 'domain_content_status';

    protected $fillable = ['code', 'label'];

    public const DRAFT = 1;
    public const SCHEDULED = 2;
    public const PUBLISHED = 3;
    public const CANCELED = 4;

    /**
     * Retorna status de rascunho.
     */
    public static function draft(): int
    {
        return self::DRAFT;
    }

    /**
     * Retorna status agendado.
     */
    public static function scheduled(): int
    {
        return self::SCHEDULED;
    }

    /**
     * Retorna status publicado.
     */
    public static function published(): int
    {
        return self::PUBLISHED;
    }

    /**
     * Retorna status cancelado.
     */
    public static function canceled(): int
    {
        return self::CANCELED;
    }

    /**
     * Verifica se esta publicado.
     */
    public function isPublished(): bool
    {
        return $this->id === self::PUBLISHED;
    }

    /**
     * Verifica se pode ser editado.
     */
    public function isEditable(): bool
    {
        return in_array($this->id, [self::DRAFT, self::SCHEDULED]);
    }
}
