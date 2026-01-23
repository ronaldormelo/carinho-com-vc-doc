<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasAuditLog;
use App\Models\Domain\DomainDealStatus;

/**
 * Deal/Oportunidade comercial
 * 
 * Representa uma oportunidade de negócio no pipeline comercial.
 * Inclui práticas tradicionais de vendas:
 * - Probabilidade de fechamento (para forecast)
 * - Valor ponderado (valor * probabilidade)
 * - Data prevista de fechamento
 * - Próximo passo/ação
 */
class Deal extends Model
{
    use HasFactory, HasAuditLog;

    protected $table = 'deals';

    protected $fillable = [
        'lead_id',
        'stage_id',
        'value_estimated',
        'status_id',
        // Campos de previsibilidade (práticas tradicionais)
        'probability',
        'expected_close_date',
        'next_action',
        'next_action_date',
    ];

    protected $casts = [
        'value_estimated' => 'decimal:2',
        'weighted_value' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'next_action_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Probabilidades padrão (prática tradicional de vendas)
    public const PROBABILITY_LOW = 10;       // Baixa - Primeiro contato
    public const PROBABILITY_MEDIUM_LOW = 25; // Média-baixa - Em qualificação
    public const PROBABILITY_MEDIUM = 50;     // Média - Proposta em análise
    public const PROBABILITY_MEDIUM_HIGH = 75; // Média-alta - Negociação final
    public const PROBABILITY_HIGH = 90;       // Alta - Fechamento iminente

    // Campos auditados
    protected array $audited = ['stage_id', 'value_estimated', 'status_id', 'probability', 'expected_close_date'];
    protected string $logName = 'deals';

    // Relacionamentos
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function stage()
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    public function status()
    {
        return $this->belongsTo(DomainDealStatus::class, 'status_id');
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status_id', DomainDealStatus::OPEN);
    }

    public function scopeWon($query)
    {
        return $query->where('status_id', DomainDealStatus::WON);
    }

    public function scopeLost($query)
    {
        return $query->where('status_id', DomainDealStatus::LOST);
    }

    public function scopeInStage($query, int $stageId)
    {
        return $query->where('stage_id', $stageId);
    }

    public function scopeCreatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeWithMinValue($query, float $minValue)
    {
        return $query->where('value_estimated', '>=', $minValue);
    }

    /**
     * Deals com alta probabilidade (>= 75%)
     */
    public function scopeHighProbability($query)
    {
        return $query->where('probability', '>=', self::PROBABILITY_MEDIUM_HIGH);
    }

    /**
     * Deals com baixa probabilidade (<= 25%)
     */
    public function scopeLowProbability($query)
    {
        return $query->where('probability', '<=', self::PROBABILITY_MEDIUM_LOW);
    }

    /**
     * Deals com fechamento previsto no período
     */
    public function scopeExpectedCloseBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('expected_close_date', [$startDate, $endDate]);
    }

    /**
     * Deals com fechamento previsto em atraso
     */
    public function scopeOverdueClose($query)
    {
        return $query->open()
            ->whereNotNull('expected_close_date')
            ->where('expected_close_date', '<', now());
    }

    /**
     * Deals com próxima ação pendente
     */
    public function scopeWithPendingAction($query)
    {
        return $query->open()
            ->whereNotNull('next_action')
            ->where(function ($q) {
                $q->whereNull('next_action_date')
                  ->orWhere('next_action_date', '<=', now());
            });
    }

    // ==========================================
    // MÉTODOS DE NEGÓCIO
    // ==========================================

    public function isOpen(): bool
    {
        return $this->status_id === DomainDealStatus::OPEN;
    }

    public function isWon(): bool
    {
        return $this->status_id === DomainDealStatus::WON;
    }

    public function isLost(): bool
    {
        return $this->status_id === DomainDealStatus::LOST;
    }

    public function canMoveToStage(int $stageId): bool
    {
        return $this->isOpen() && PipelineStage::where('id', $stageId)->where('active', true)->exists();
    }

    public function moveToNextStage(): bool
    {
        if (!$this->isOpen()) {
            return false;
        }

        $nextStage = $this->stage->getNextStage();
        if (!$nextStage) {
            return false;
        }

        $this->stage_id = $nextStage->id;
        return $this->save();
    }

    public function getLatestProposal(): ?Proposal
    {
        return $this->proposals()->latest()->first();
    }

    public function getDaysInCurrentStage(): int
    {
        return $this->updated_at->diffInDays(now());
    }

    public function getTotalDaysInPipeline(): int
    {
        return $this->created_at->diffInDays(now());
    }

    // ==========================================
    // PROBABILIDADE E FORECAST (Práticas Tradicionais)
    // ==========================================

    /**
     * Verificar se é alta probabilidade
     */
    public function isHighProbability(): bool
    {
        return $this->probability >= self::PROBABILITY_MEDIUM_HIGH;
    }

    /**
     * Verificar se fechamento está em atraso
     */
    public function isOverdueClose(): bool
    {
        if (!$this->isOpen() || $this->expected_close_date === null) {
            return false;
        }

        return $this->expected_close_date->lt(now());
    }

    /**
     * Obter label da probabilidade
     */
    public function getProbabilityLabelAttribute(): string
    {
        return match (true) {
            $this->probability >= 90 => 'Muito Alta',
            $this->probability >= 75 => 'Alta',
            $this->probability >= 50 => 'Média',
            $this->probability >= 25 => 'Baixa',
            default => 'Muito Baixa',
        };
    }

    /**
     * Atualizar probabilidade com base no estágio
     * (Prática tradicional: probabilidade aumenta conforme avança no pipeline)
     */
    public function updateProbabilityFromStage(): void
    {
        $stageOrder = $this->stage?->stage_order ?? 1;
        $totalStages = PipelineStage::where('active', true)->count();

        if ($totalStages <= 1) {
            return;
        }

        // Calcula probabilidade baseada na posição no pipeline
        // Primeiro estágio = 10%, último estágio antes de ganho = 90%
        $baseProb = 10;
        $maxProb = 90;
        $increment = ($maxProb - $baseProb) / ($totalStages - 1);

        $this->probability = (int) round($baseProb + ($stageOrder - 1) * $increment);
    }

    /**
     * Verificar se tem próxima ação pendente
     */
    public function hasOverdueAction(): bool
    {
        if (empty($this->next_action) || !$this->isOpen()) {
            return false;
        }

        if ($this->next_action_date === null) {
            return true; // Sem data definida = pendente
        }

        return $this->next_action_date->lt(now());
    }

    /**
     * Definir próxima ação
     */
    public function setNextAction(string $action, ?\DateTime $dueDate = null): void
    {
        $this->next_action = $action;
        $this->next_action_date = $dueDate;
        $this->save();
    }

    /**
     * Limpar próxima ação (quando concluída)
     */
    public function clearNextAction(): void
    {
        $this->next_action = null;
        $this->next_action_date = null;
        $this->save();
    }

    // ==========================================
    // MÉTODOS ESTÁTICOS DE FORECAST
    // ==========================================

    /**
     * Calcular forecast de receita para um período
     * (Soma dos valores ponderados de deals abertos)
     */
    public static function getForecast($startDate = null, $endDate = null): array
    {
        $query = self::open();

        if ($startDate && $endDate) {
            $query->expectedCloseBetween($startDate, $endDate);
        }

        $deals = $query->get();

        return [
            'total_deals' => $deals->count(),
            'total_value' => $deals->sum('value_estimated'),
            'weighted_value' => $deals->sum('weighted_value'),
            'by_probability' => [
                'high' => $deals->where('probability', '>=', 75)->sum('weighted_value'),
                'medium' => $deals->whereBetween('probability', [25, 74])->sum('weighted_value'),
                'low' => $deals->where('probability', '<', 25)->sum('weighted_value'),
            ],
        ];
    }

    /**
     * Obter deals com fechamento previsto este mês
     */
    public static function getThisMonthForecast(): array
    {
        return self::getForecast(now()->startOfMonth(), now()->endOfMonth());
    }

    /**
     * Obter deals com fechamento previsto próximo mês
     */
    public static function getNextMonthForecast(): array
    {
        return self::getForecast(
            now()->addMonth()->startOfMonth(),
            now()->addMonth()->endOfMonth()
        );
    }
}
