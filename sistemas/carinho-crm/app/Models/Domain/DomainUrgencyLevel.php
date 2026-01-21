<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCacheableQueries;

class DomainUrgencyLevel extends Model
{
    use HasCacheableQueries;

    protected $table = 'domain_urgency_level';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes para códigos
    public const HOJE = 1;
    public const SEMANA = 2;
    public const SEM_DATA = 3;

    public function leads()
    {
        return $this->hasMany(\App\Models\Lead::class, 'urgency_id');
    }
}
