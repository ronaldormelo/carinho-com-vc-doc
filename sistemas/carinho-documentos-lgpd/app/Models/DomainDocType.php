<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipo de documento (dominio).
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainDocType extends Model
{
    protected $table = 'domain_doc_type';

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes para tipos de documento
    public const CONTRATO_CLIENTE = 1;
    public const CONTRATO_CUIDADOR = 2;
    public const TERMOS = 3;
    public const PRIVACIDADE = 4;

    public const CODES = [
        self::CONTRATO_CLIENTE => 'contrato_cliente',
        self::CONTRATO_CUIDADOR => 'contrato_cuidador',
        self::TERMOS => 'termos',
        self::PRIVACIDADE => 'privacidade',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(DocumentTemplate::class, 'doc_type_id');
    }

    public function retentionPolicies(): HasMany
    {
        return $this->hasMany(RetentionPolicy::class, 'doc_type_id');
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
