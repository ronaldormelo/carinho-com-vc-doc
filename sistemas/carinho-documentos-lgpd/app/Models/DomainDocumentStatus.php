<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Status de documento (dominio).
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainDocumentStatus extends Model
{
    protected $table = 'domain_document_status';

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes
    public const DRAFT = 1;
    public const SIGNED = 2;
    public const ARCHIVED = 3;

    public const CODES = [
        self::DRAFT => 'draft',
        self::SIGNED => 'signed',
        self::ARCHIVED => 'archived',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'status_id');
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
