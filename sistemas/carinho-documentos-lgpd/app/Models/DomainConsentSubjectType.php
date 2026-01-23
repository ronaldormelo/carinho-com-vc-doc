<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipo de titular de consentimento (dominio).
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainConsentSubjectType extends Model
{
    protected $table = 'domain_consent_subject_type';

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes
    public const CLIENT = 1;
    public const CAREGIVER = 2;

    public const CODES = [
        self::CLIENT => 'client',
        self::CAREGIVER => 'caregiver',
    ];

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class, 'subject_type_id');
    }

    public function dataRequests(): HasMany
    {
        return $this->hasMany(DataRequest::class, 'subject_type_id');
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
