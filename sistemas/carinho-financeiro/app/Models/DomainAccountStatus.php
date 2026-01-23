<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainAccountStatus extends Model
{
    public $timestamps = false;
    protected $table = 'domain_account_status';
    protected $fillable = ['id', 'code', 'label'];

    public const ACTIVE = 1;
    public const INACTIVE = 2;

    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public function isActive(): bool
    {
        return $this->id === self::ACTIVE;
    }
}
