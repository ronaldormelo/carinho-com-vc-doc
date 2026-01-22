<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipos de criativos (midia).
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainCreativeType extends Model
{
    public $timestamps = false;

    protected $table = 'domain_creative_type';

    protected $fillable = ['code', 'label'];

    public const IMAGE = 1;
    public const VIDEO = 2;
    public const TEXT = 3;

    /**
     * Retorna tipo imagem.
     */
    public static function image(): int
    {
        return self::IMAGE;
    }

    /**
     * Retorna tipo video.
     */
    public static function video(): int
    {
        return self::VIDEO;
    }

    /**
     * Retorna tipo texto.
     */
    public static function text(): int
    {
        return self::TEXT;
    }

    /**
     * Verifica se e um tipo de midia (imagem ou video).
     */
    public function isMedia(): bool
    {
        return in_array($this->id, [self::IMAGE, self::VIDEO]);
    }

    /**
     * Retorna extensoes permitidas para o tipo.
     */
    public function getAllowedExtensions(): array
    {
        return match ($this->id) {
            self::IMAGE => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            self::VIDEO => ['mp4', 'mov', 'avi', 'webm'],
            default => [],
        };
    }
}
