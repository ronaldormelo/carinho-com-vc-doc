<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainPayoutStatus extends Model
{
    public $timestamps = false;
    protected $table = 'domain_payout_status';
    protected $fillable = ['id', 'code', 'label'];

    public const OPEN = 1;
    public const PAID = 2;
    public const CANCELED = 3;

    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public function isOpen(): bool
    {
        return $this->id === self::OPEN;
    }

    public function isPaid(): bool
    {
        return $this->id === self::PAID;
    }

    public function isCanceled(): bool
    {
        return $this->id === self::CANCELED;
    }
}
