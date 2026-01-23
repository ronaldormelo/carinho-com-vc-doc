<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainReconciliationStatus extends Model
{
    public $timestamps = false;
    protected $table = 'domain_reconciliation_status';
    protected $fillable = ['id', 'code', 'label'];

    public const OPEN = 1;
    public const CLOSED = 2;

    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public function isOpen(): bool
    {
        return $this->id === self::OPEN;
    }

    public function isClosed(): bool
    {
        return $this->id === self::CLOSED;
    }
}
