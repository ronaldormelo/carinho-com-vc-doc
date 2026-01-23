<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainSkillLevel extends Model
{
    protected $table = 'domain_skill_level';

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label'];

    public const BASICO = 1;
    public const INTERMEDIARIO = 2;
    public const AVANCADO = 3;

    public static function byCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
