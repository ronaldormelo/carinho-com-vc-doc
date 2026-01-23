<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainDocumentStatus extends Model
{
    protected $table = 'domain_document_status';

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label'];

    public const PENDING = 1;
    public const VERIFIED = 2;
    public const REJECTED = 3;

    public static function pending(): self
    {
        return static::find(self::PENDING);
    }

    public static function verified(): self
    {
        return static::find(self::VERIFIED);
    }

    public static function rejected(): self
    {
        return static::find(self::REJECTED);
    }

    public static function byCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
