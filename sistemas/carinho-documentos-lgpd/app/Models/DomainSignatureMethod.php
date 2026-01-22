<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Metodo de assinatura (dominio).
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainSignatureMethod extends Model
{
    protected $table = 'domain_signature_method';

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes
    public const OTP = 1;
    public const CLICK = 2;
    public const CERTIFICATE = 3;

    public const CODES = [
        self::OTP => 'otp',
        self::CLICK => 'click',
        self::CERTIFICATE => 'certificate',
    ];

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class, 'method_id');
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
