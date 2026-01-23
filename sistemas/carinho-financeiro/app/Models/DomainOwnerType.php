<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainOwnerType extends Model
{
    public $timestamps = false;
    protected $table = 'domain_owner_type';
    protected $fillable = ['id', 'code', 'label'];

    public const CLIENT = 1;
    public const CAREGIVER = 2;
    public const COMPANY = 3;

    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public function isClient(): bool
    {
        return $this->id === self::CLIENT;
    }

    public function isCaregiver(): bool
    {
        return $this->id === self::CAREGIVER;
    }

    public function isCompany(): bool
    {
        return $this->id === self::COMPANY;
    }
}
