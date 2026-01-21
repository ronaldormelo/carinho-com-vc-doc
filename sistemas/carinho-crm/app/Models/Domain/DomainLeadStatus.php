<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCacheableQueries;

class DomainLeadStatus extends Model
{
    use HasCacheableQueries;

    protected $table = 'domain_lead_status';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes para códigos
    public const NEW = 1;
    public const TRIAGE = 2;
    public const PROPOSAL = 3;
    public const ACTIVE = 4;
    public const LOST = 5;

    public function leads()
    {
        return $this->hasMany(\App\Models\Lead::class, 'status_id');
    }

    /**
     * Status que representa leads ativos no pipeline
     */
    public static function activeStatuses(): array
    {
        return [self::NEW, self::TRIAGE, self::PROPOSAL];
    }

    /**
     * Status finais (lead saiu do pipeline)
     */
    public static function finalStatuses(): array
    {
        return [self::ACTIVE, self::LOST];
    }
}
