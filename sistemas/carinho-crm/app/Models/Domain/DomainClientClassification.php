<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCacheableQueries;

/**
 * Classificação de clientes (Prática tradicional ABC)
 * 
 * Permite segmentar clientes por valor/potencial para priorização de atendimento.
 */
class DomainClientClassification extends Model
{
    use HasCacheableQueries;

    protected $table = 'domain_client_classification';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'label', 'description', 'priority'];

    // Constantes para códigos
    public const A = 1; // Alto valor/potencial
    public const B = 2; // Valor médio
    public const C = 3; // Valor baixo

    /**
     * Relacionamento com clientes
     */
    public function clients()
    {
        return $this->hasMany(\App\Models\Client::class, 'classification_id');
    }

    /**
     * Obter classificações ordenadas por prioridade
     */
    public static function getOrdered()
    {
        return static::cacheAll()->sortBy('priority');
    }

    /**
     * Verificar se é classificação de alta prioridade
     */
    public function isHighPriority(): bool
    {
        return $this->id === self::A;
    }
}
