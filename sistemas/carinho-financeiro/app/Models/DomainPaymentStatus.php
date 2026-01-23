<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainPaymentStatus extends Model
{
    public $timestamps = false;
    protected $table = 'domain_payment_status';
    protected $fillable = ['id', 'code', 'label'];

    public const PENDING = 1;
    public const PAID = 2;
    public const FAILED = 3;
    public const REFUNDED = 4;

    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public function isPending(): bool
    {
        return $this->id === self::PENDING;
    }

    public function isPaid(): bool
    {
        return $this->id === self::PAID;
    }

    public function isFailed(): bool
    {
        return $this->id === self::FAILED;
    }

    public function isRefunded(): bool
    {
        return $this->id === self::REFUNDED;
    }
}
