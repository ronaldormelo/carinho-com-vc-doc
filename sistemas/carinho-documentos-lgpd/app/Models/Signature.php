<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Assinatura de documento.
 *
 * @property int $id
 * @property int $document_id
 * @property int $signer_type_id
 * @property int $signer_id
 * @property Carbon $signed_at
 * @property int $method_id
 * @property string $ip_address
 */
class Signature extends Model
{
    protected $table = 'signatures';

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'signer_type_id',
        'signer_id',
        'signed_at',
        'method_id',
        'ip_address',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function signerType(): BelongsTo
    {
        return $this->belongsTo(DomainSignerType::class, 'signer_type_id');
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(DomainSignatureMethod::class, 'method_id');
    }

    /**
     * Verifica se a assinatura eh de cliente.
     */
    public function isClient(): bool
    {
        return $this->signer_type_id === DomainSignerType::CLIENT;
    }

    /**
     * Verifica se a assinatura eh de cuidador.
     */
    public function isCaregiver(): bool
    {
        return $this->signer_type_id === DomainSignerType::CAREGIVER;
    }

    /**
     * Verifica se a assinatura eh da empresa.
     */
    public function isCompany(): bool
    {
        return $this->signer_type_id === DomainSignerType::COMPANY;
    }

    /**
     * Gera hash unico para verificacao da assinatura.
     */
    public function generateVerificationHash(): string
    {
        $data = implode('|', [
            $this->id,
            $this->document_id,
            $this->signer_type_id,
            $this->signer_id,
            $this->signed_at->toIso8601String(),
            $this->ip_address,
        ]);

        return hash_hmac('sha256', $data, config('app.key'));
    }

    /**
     * Verifica assinatura.
     */
    public function verify(string $hash): bool
    {
        return hash_equals($this->generateVerificationHash(), $hash);
    }

    /**
     * Busca assinaturas por documento.
     */
    public static function findByDocument(int $documentId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('document_id', $documentId)
            ->with(['signerType', 'method'])
            ->orderBy('signed_at', 'desc')
            ->get();
    }
}
