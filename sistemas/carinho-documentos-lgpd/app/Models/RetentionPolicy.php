<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Politica de retencao de documentos.
 *
 * @property int $id
 * @property int $doc_type_id
 * @property int $retention_days
 */
class RetentionPolicy extends Model
{
    protected $table = 'retention_policies';

    public $timestamps = false;

    protected $fillable = [
        'doc_type_id',
        'retention_days',
    ];

    protected $casts = [
        'retention_days' => 'integer',
    ];

    public function docType(): BelongsTo
    {
        return $this->belongsTo(DomainDocType::class, 'doc_type_id');
    }

    /**
     * Obtem politica por tipo de documento.
     */
    public static function getByDocType(int $docTypeId): ?self
    {
        return static::where('doc_type_id', $docTypeId)->first();
    }

    /**
     * Obtem dias de retencao por tipo, com fallback para config.
     */
    public static function getRetentionDays(int $docTypeId): int
    {
        $policy = static::getByDocType($docTypeId);

        if ($policy) {
            return $policy->retention_days;
        }

        // Fallback para configuracao padrao
        $code = DomainDocType::CODES[$docTypeId] ?? null;

        return config("documentos.retention.default_days.{$code}", 1825);
    }

    /**
     * Calcula data de expiracao baseado na politica.
     */
    public function getExpirationDate(\Illuminate\Support\Carbon $createdAt): \Illuminate\Support\Carbon
    {
        return $createdAt->addDays($this->retention_days);
    }

    /**
     * Verifica se um documento expirou.
     */
    public function isExpired(\Illuminate\Support\Carbon $createdAt): bool
    {
        return now()->isAfter($this->getExpirationDate($createdAt));
    }
}
