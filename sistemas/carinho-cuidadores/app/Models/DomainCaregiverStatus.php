<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainCaregiverStatus extends Model
{
    protected $table = 'domain_caregiver_status';

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label'];

    public const PENDING = 1;
    public const ACTIVE = 2;
    public const INACTIVE = 3;
    public const BLOCKED = 4;

    public static function pending(): self
    {
        return static::find(self::PENDING);
    }

    public static function active(): self
    {
        return static::find(self::ACTIVE);
    }

    public static function inactive(): self
    {
        return static::find(self::INACTIVE);
    }

    public static function blocked(): self
    {
        return static::find(self::BLOCKED);
    }

    public static function byCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
