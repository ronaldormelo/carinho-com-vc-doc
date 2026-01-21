<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCacheableQueries;

class DomainServiceType extends Model
{
    use HasCacheableQueries;

    protected $table = 'domain_service_type';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes para códigos
    public const HORISTA = 1;
    public const DIARIO = 2;
    public const MENSAL = 3;

    public function leads()
    {
        return $this->hasMany(\App\Models\Lead::class, 'service_type_id');
    }

    public function proposals()
    {
        return $this->hasMany(\App\Models\Proposal::class, 'service_type_id');
    }
}
