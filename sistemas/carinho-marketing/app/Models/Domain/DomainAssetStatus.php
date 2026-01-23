<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Status de assets (midia).
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainAssetStatus extends Model
{
    public $timestamps = false;

    protected $table = 'domain_asset_status';

    protected $fillable = ['code', 'label'];

    public const DRAFT = 1;
    public const APPROVED = 2;
    public const PUBLISHED = 3;

    /**
     * Retorna status de rascunho.
     */
    public static function draft(): int
    {
        return self::DRAFT;
    }

    /**
     * Retorna status aprovado.
     */
    public static function approved(): int
    {
        return self::APPROVED;
    }

    /**
     * Retorna status publicado.
     */
    public static function published(): int
    {
        return self::PUBLISHED;
    }

    /**
     * Verifica se esta aprovado para uso.
     */
    public function isApproved(): bool
    {
        return in_array($this->id, [self::APPROVED, self::PUBLISHED]);
    }
}
