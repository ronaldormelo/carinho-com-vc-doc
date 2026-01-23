<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainPaymentMethod extends Model
{
    public $timestamps = false;
    protected $table = 'domain_payment_method';
    protected $fillable = ['id', 'code', 'label'];

    public const PIX = 1;
    public const BOLETO = 2;
    public const CARD = 3;

    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public function isPix(): bool
    {
        return $this->id === self::PIX;
    }

    public function isBoleto(): bool
    {
        return $this->id === self::BOLETO;
    }

    public function isCard(): bool
    {
        return $this->id === self::CARD;
    }
}
