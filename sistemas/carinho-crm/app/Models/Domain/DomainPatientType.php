<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCacheableQueries;

class DomainPatientType extends Model
{
    use HasCacheableQueries;

    protected $table = 'domain_patient_type';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes para códigos
    public const IDOSO = 1;
    public const PCD = 2;
    public const TEA = 3;
    public const POS_OPERATORIO = 4;

    public function careNeeds()
    {
        return $this->hasMany(\App\Models\CareNeed::class, 'patient_type_id');
    }
}
