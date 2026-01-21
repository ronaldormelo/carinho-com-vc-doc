<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCacheableQueries;

class DomainContractStatus extends Model
{
    use HasCacheableQueries;

    protected $table = 'domain_contract_status';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes para códigos
    public const DRAFT = 1;
    public const SIGNED = 2;
    public const ACTIVE = 3;
    public const CLOSED = 4;

    public function contracts()
    {
        return $this->hasMany(\App\Models\Contract::class, 'status_id');
    }

    /**
     * Status que indicam contrato vigente
     */
    public static function activeStatuses(): array
    {
        return [self::SIGNED, self::ACTIVE];
    }
}
