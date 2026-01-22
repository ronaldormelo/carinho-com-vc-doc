<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainContractStatus extends Model
{
    protected $table = 'domain_contract_status';

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label'];

    public const DRAFT = 1;
    public const SIGNED = 2;
    public const ACTIVE = 3;
    public const CLOSED = 4;

    public static function draft(): self
    {
        return static::find(self::DRAFT);
    }

    public static function signed(): self
    {
        return static::find(self::SIGNED);
    }

    public static function active(): self
    {
        return static::find(self::ACTIVE);
    }

    public static function closed(): self
    {
        return static::find(self::CLOSED);
    }

    public static function byCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
