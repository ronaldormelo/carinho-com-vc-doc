<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Domain\DomainPatientType;

class CareNeed extends Model
{
    use HasFactory;

    protected $table = 'care_needs';
    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'patient_type_id',
        'conditions_json',
        'notes',
    ];

    protected $casts = [
        'conditions_json' => 'array',
    ];

    // Relacionamentos
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function patientType()
    {
        return $this->belongsTo(DomainPatientType::class, 'patient_type_id');
    }

    // Scopes
    public function scopeByPatientType($query, int $patientTypeId)
    {
        return $query->where('patient_type_id', $patientTypeId);
    }

    public function scopeIdosos($query)
    {
        return $query->where('patient_type_id', DomainPatientType::IDOSO);
    }

    public function scopePcd($query)
    {
        return $query->where('patient_type_id', DomainPatientType::PCD);
    }

    public function scopeTea($query)
    {
        return $query->where('patient_type_id', DomainPatientType::TEA);
    }

    public function scopePosOperatorio($query)
    {
        return $query->where('patient_type_id', DomainPatientType::POS_OPERATORIO);
    }

    // Métodos de negócio
    public function hasCondition(string $condition): bool
    {
        return in_array($condition, $this->conditions_json ?? []);
    }

    public function addCondition(string $condition): void
    {
        $conditions = $this->conditions_json ?? [];
        if (!in_array($condition, $conditions)) {
            $conditions[] = $condition;
            $this->conditions_json = $conditions;
        }
    }

    public function removeCondition(string $condition): void
    {
        $conditions = $this->conditions_json ?? [];
        $this->conditions_json = array_values(array_diff($conditions, [$condition]));
    }
}
