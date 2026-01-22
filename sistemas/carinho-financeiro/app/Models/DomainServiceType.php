<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainServiceType extends Model
{
    public $timestamps = false;
    protected $table = 'domain_service_type';
    protected $fillable = ['id', 'code', 'label'];

    public const HORISTA = 1;
    public const DIARIO = 2;
    public const MENSAL = 3;

    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public function isHorista(): bool
    {
        return $this->id === self::HORISTA;
    }

    public function isDiario(): bool
    {
        return $this->id === self::DIARIO;
    }

    public function isMensal(): bool
    {
        return $this->id === self::MENSAL;
    }

    /**
     * Retorna o percentual de comissão do cuidador para este tipo de serviço.
     */
    public function getCaregiverCommissionPercent(): float
    {
        $commissions = config('financeiro.commission.by_service_type');
        
        return $commissions[$this->code]['caregiver_percent'] 
            ?? config('financeiro.commission.caregiver_percent', 70);
    }
}
