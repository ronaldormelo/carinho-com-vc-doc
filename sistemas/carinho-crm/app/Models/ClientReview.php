<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Revisões periódicas de clientes
 * 
 * Prática tradicional de gestão de relacionamento para:
 * - Avaliar satisfação do cliente
 * - Identificar oportunidades de melhoria
 * - Prevenir churn
 * - Documentar intenção de renovação
 */
class ClientReview extends Model
{
    use HasFactory;

    protected $table = 'client_reviews';

    protected $fillable = [
        'client_id',
        'reviewed_by',
        'review_date',
        'satisfaction_score',
        'service_quality_score',
        'contract_renewal_intent',
        'observations',
        'action_items',
        'next_review_date',
    ];

    protected $casts = [
        'review_date' => 'date',
        'next_review_date' => 'date',
        'satisfaction_score' => 'integer',
        'service_quality_score' => 'integer',
        'contract_renewal_intent' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeWithRenewalIntent($query)
    {
        return $query->where('contract_renewal_intent', true);
    }

    public function scopeWithoutRenewalIntent($query)
    {
        return $query->where('contract_renewal_intent', false);
    }

    public function scopeLowSatisfaction($query, int $threshold = 3)
    {
        return $query->where('satisfaction_score', '<=', $threshold);
    }

    public function scopeHighSatisfaction($query, int $threshold = 4)
    {
        return $query->where('satisfaction_score', '>=', $threshold);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('review_date', [$startDate, $endDate]);
    }

    public function scopeReviewedBy($query, int $userId)
    {
        return $query->where('reviewed_by', $userId);
    }

    // Métodos de negócio
    
    /**
     * Classificar satisfação em texto
     */
    public function getSatisfactionLabelAttribute(): string
    {
        return match ($this->satisfaction_score) {
            5 => 'Excelente',
            4 => 'Bom',
            3 => 'Regular',
            2 => 'Ruim',
            1 => 'Péssimo',
            default => 'Não avaliado',
        };
    }

    /**
     * Verificar se revisão indica risco de churn
     */
    public function isChurnRisk(): bool
    {
        // Risco se: baixa satisfação OU sem intenção de renovar
        return ($this->satisfaction_score !== null && $this->satisfaction_score <= 2)
            || $this->contract_renewal_intent === false;
    }

    /**
     * Verificar se revisão indica cliente promotor (potencial indicação)
     */
    public function isPromoter(): bool
    {
        return $this->satisfaction_score !== null 
            && $this->satisfaction_score >= 4 
            && $this->contract_renewal_intent === true;
    }

    /**
     * Calcular score geral (média de satisfação e qualidade)
     */
    public function getOverallScoreAttribute(): ?float
    {
        $scores = array_filter([
            $this->satisfaction_score,
            $this->service_quality_score,
        ], fn($s) => $s !== null);

        if (empty($scores)) {
            return null;
        }

        return round(array_sum($scores) / count($scores), 1);
    }

    /**
     * Verificar se há ações pendentes
     */
    public function hasActionItems(): bool
    {
        return !empty($this->action_items);
    }

    /**
     * Obter lista de ações como array
     */
    public function getActionItemsListAttribute(): array
    {
        if (empty($this->action_items)) {
            return [];
        }

        // Assume que ações estão separadas por quebra de linha
        return array_filter(
            array_map('trim', explode("\n", $this->action_items))
        );
    }
}
