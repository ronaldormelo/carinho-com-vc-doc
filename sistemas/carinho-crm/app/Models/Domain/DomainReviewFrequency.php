<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCacheableQueries;

/**
 * Frequência de revisão de clientes
 * 
 * Define intervalos padronizados para revisões periódicas de clientes,
 * prática tradicional para manutenção de relacionamento e prevenção de churn.
 */
class DomainReviewFrequency extends Model
{
    use HasCacheableQueries;

    protected $table = 'domain_review_frequency';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'label', 'days'];

    // Constantes para códigos
    public const MONTHLY = 1;      // 30 dias
    public const BIMONTHLY = 2;    // 60 dias
    public const QUARTERLY = 3;    // 90 dias
    public const SEMIANNUAL = 4;   // 180 dias
    public const ANNUAL = 5;       // 365 dias

    /**
     * Relacionamento com clientes
     */
    public function clients()
    {
        return $this->hasMany(\App\Models\Client::class, 'review_frequency_id');
    }

    /**
     * Calcular próxima data de revisão a partir de uma data base
     */
    public function calculateNextReviewDate(\DateTime $fromDate = null): \DateTime
    {
        $from = $fromDate ?? now();
        return $from->copy()->addDays($this->days);
    }

    /**
     * Obter frequência recomendada para classificação de cliente
     */
    public static function getRecommendedForClassification(int $classificationId): int
    {
        return match ($classificationId) {
            DomainClientClassification::A => self::MONTHLY,
            DomainClientClassification::B => self::QUARTERLY,
            DomainClientClassification::C => self::SEMIANNUAL,
            default => self::QUARTERLY,
        };
    }
}
