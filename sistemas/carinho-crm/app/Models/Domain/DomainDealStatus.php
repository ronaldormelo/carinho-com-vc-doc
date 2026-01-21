<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCacheableQueries;

class DomainDealStatus extends Model
{
    use HasCacheableQueries;

    protected $table = 'domain_deal_status';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes para códigos
    public const OPEN = 1;
    public const WON = 2;
    public const LOST = 3;

    public function deals()
    {
        return $this->hasMany(\App\Models\Deal::class, 'status_id');
    }
}
