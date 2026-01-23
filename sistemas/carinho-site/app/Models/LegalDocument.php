<?php

namespace App\Models;

use App\Models\Domain\DomainLegalDocType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Documento legal (politicas e termos).
 *
 * @property int $id
 * @property int $doc_type_id
 * @property string $version
 * @property string $content
 * @property \Carbon\Carbon|null $published_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class LegalDocument extends Model
{
    protected $table = 'legal_documents';

    protected $fillable = [
        'doc_type_id',
        'version',
        'content',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Relacao com tipo de documento.
     */
    public function docType(): BelongsTo
    {
        return $this->belongsTo(DomainLegalDocType::class, 'doc_type_id');
    }

    /**
     * Scope para documentos publicados.
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Obtem documento mais recente por tipo.
     */
    public static function getLatestByType(int $typeId): ?self
    {
        return Cache::remember(
            "legal_doc_{$typeId}",
            config('site.cache.legal_docs', 86400),
            fn () => static::published()
                ->where('doc_type_id', $typeId)
                ->orderByDesc('published_at')
                ->first()
        );
    }

    /**
     * Obtem politica de privacidade.
     */
    public static function getPrivacyPolicy(): ?self
    {
        return static::getLatestByType(DomainLegalDocType::PRIVACY);
    }

    /**
     * Obtem termos de uso.
     */
    public static function getTermsOfService(): ?self
    {
        return static::getLatestByType(DomainLegalDocType::TERMS);
    }

    /**
     * Obtem politica de cancelamento.
     */
    public static function getCancellationPolicy(): ?self
    {
        return static::getLatestByType(DomainLegalDocType::CANCELLATION);
    }

    /**
     * Obtem politica de emergencias.
     */
    public static function getEmergencyPolicy(): ?self
    {
        return static::getLatestByType(DomainLegalDocType::EMERGENCY);
    }

    /**
     * Obtem politica de pagamento.
     */
    public static function getPaymentPolicy(): ?self
    {
        return static::getLatestByType(DomainLegalDocType::PAYMENT);
    }

    /**
     * Limpa cache do documento.
     */
    public function clearCache(): void
    {
        Cache::forget("legal_doc_{$this->doc_type_id}");
    }
}
