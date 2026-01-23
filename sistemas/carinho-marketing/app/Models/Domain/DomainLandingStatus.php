<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Status de landing pages.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainLandingStatus extends Model
{
    public $timestamps = false;

    protected $table = 'domain_landing_status';

    protected $fillable = ['code', 'label'];

    public const DRAFT = 1;
    public const PUBLISHED = 2;
    public const ARCHIVED = 3;

    /**
     * Retorna status de rascunho.
     */
    public static function draft(): int
    {
        return self::DRAFT;
    }

    /**
     * Retorna status publicado.
     */
    public static function published(): int
    {
        return self::PUBLISHED;
    }

    /**
     * Retorna status arquivado.
     */
    public static function archived(): int
    {
        return self::ARCHIVED;
    }

    /**
     * Verifica se a landing page esta publicada.
     */
    public function isPublished(): bool
    {
        return $this->id === self::PUBLISHED;
    }

    /**
     * Verifica se pode ser editada.
     */
    public function isEditable(): bool
    {
        return in_array($this->id, [self::DRAFT, self::PUBLISHED]);
    }
}
