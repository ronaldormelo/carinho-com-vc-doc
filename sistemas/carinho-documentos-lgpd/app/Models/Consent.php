<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Consentimento LGPD.
 *
 * @property int $id
 * @property int $subject_type_id
 * @property int $subject_id
 * @property string $consent_type
 * @property Carbon $granted_at
 * @property string $source
 * @property Carbon|null $revoked_at
 */
class Consent extends Model
{
    protected $table = 'consents';

    public $timestamps = false;

    protected $fillable = [
        'subject_type_id',
        'subject_id',
        'consent_type',
        'granted_at',
        'source',
        'revoked_at',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    // Tipos de consentimento
    public const TYPE_DATA_PROCESSING = 'data_processing';
    public const TYPE_MARKETING = 'marketing';
    public const TYPE_SHARING = 'sharing';
    public const TYPE_PROFILING = 'profiling';
    public const TYPE_COOKIES = 'cookies';

    public const TYPES = [
        self::TYPE_DATA_PROCESSING => 'Tratamento de dados pessoais',
        self::TYPE_MARKETING => 'Comunicacoes de marketing',
        self::TYPE_SHARING => 'Compartilhamento com terceiros',
        self::TYPE_PROFILING => 'Perfilamento automatizado',
        self::TYPE_COOKIES => 'Uso de cookies',
    ];

    // Fontes de consentimento
    public const SOURCE_WEBSITE = 'website';
    public const SOURCE_APP = 'app';
    public const SOURCE_WHATSAPP = 'whatsapp';
    public const SOURCE_CONTRACT = 'contract';

    public function subjectType(): BelongsTo
    {
        return $this->belongsTo(DomainConsentSubjectType::class, 'subject_type_id');
    }

    /**
     * Verifica se o consentimento esta ativo.
     */
    public function isActive(): bool
    {
        return is_null($this->revoked_at);
    }

    /**
     * Revoga o consentimento.
     */
    public function revoke(): bool
    {
        $this->revoked_at = now();

        return $this->save();
    }

    /**
     * Verifica consentimento ativo para um titular.
     */
    public static function hasActiveConsent(int $subjectTypeId, int $subjectId, string $consentType): bool
    {
        return static::where('subject_type_id', $subjectTypeId)
            ->where('subject_id', $subjectId)
            ->where('consent_type', $consentType)
            ->whereNull('revoked_at')
            ->exists();
    }

    /**
     * Obtem todos os consentimentos ativos de um titular.
     */
    public static function getActiveForSubject(int $subjectTypeId, int $subjectId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('subject_type_id', $subjectTypeId)
            ->where('subject_id', $subjectId)
            ->whereNull('revoked_at')
            ->orderBy('granted_at', 'desc')
            ->get();
    }

    /**
     * Obtem historico completo de um titular.
     */
    public static function getHistoryForSubject(int $subjectTypeId, int $subjectId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('subject_type_id', $subjectTypeId)
            ->where('subject_id', $subjectId)
            ->orderBy('granted_at', 'desc')
            ->get();
    }

    /**
     * Scope para consentimentos ativos.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    /**
     * Scope para consentimentos revogados.
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }
}
