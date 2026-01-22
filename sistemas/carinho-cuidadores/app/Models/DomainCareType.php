<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainCareType extends Model
{
    protected $table = 'domain_care_type';

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label'];

    public const IDOSO = 1;
    public const PCD = 2;
    public const TEA = 3;
    public const POS_OPERATORIO = 4;

    public static function byCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public static function all(): \Illuminate\Database\Eloquent\Collection
    {
        return parent::all();
    }
}
