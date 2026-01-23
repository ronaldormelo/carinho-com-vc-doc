<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LossReason extends Model
{
    use HasFactory;

    protected $table = 'loss_reasons';
    public $timestamps = false;

    protected $fillable = [
        'lead_id',
        'reason',
        'details',
    ];

    // Motivos comuns de perda
    public const REASON_PRICE = 'price';
    public const REASON_COMPETITOR = 'competitor';
    public const REASON_NO_NEED = 'no_need';
    public const REASON_NO_RESPONSE = 'no_response';
    public const REASON_LOCATION = 'location';
    public const REASON_TIMING = 'timing';
    public const REASON_QUALITY = 'quality';
    public const REASON_OTHER = 'other';

    // Relacionamentos
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    // Scopes
    public function scopeByReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    public function scopePrice($query)
    {
        return $query->where('reason', self::REASON_PRICE);
    }

    public function scopeCompetitor($query)
    {
        return $query->where('reason', self::REASON_COMPETITOR);
    }

    public function scopeNoResponse($query)
    {
        return $query->where('reason', self::REASON_NO_RESPONSE);
    }

    // Métodos de negócio
    public static function availableReasons(): array
    {
        return [
            self::REASON_PRICE => 'Preço alto',
            self::REASON_COMPETITOR => 'Concorrente',
            self::REASON_NO_NEED => 'Não precisa mais',
            self::REASON_NO_RESPONSE => 'Sem resposta',
            self::REASON_LOCATION => 'Localização',
            self::REASON_TIMING => 'Timing inadequado',
            self::REASON_QUALITY => 'Qualidade',
            self::REASON_OTHER => 'Outro',
        ];
    }

    public function getReasonLabel(): string
    {
        return self::availableReasons()[$this->reason] ?? $this->reason;
    }

    /**
     * Estatísticas de motivos de perda
     */
    public static function getStatistics($startDate = null, $endDate = null): array
    {
        $query = self::query();

        if ($startDate && $endDate) {
            $query->whereHas('lead', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('updated_at', [$startDate, $endDate]);
            });
        }

        $total = $query->count();
        
        if ($total === 0) {
            return [];
        }

        $reasons = self::availableReasons();
        $stats = [];

        foreach ($reasons as $code => $label) {
            $count = (clone $query)->where('reason', $code)->count();
            $stats[$code] = [
                'label' => $label,
                'count' => $count,
                'percentage' => round(($count / $total) * 100, 2),
            ];
        }

        // Ordenar por contagem decrescente
        uasort($stats, fn($a, $b) => $b['count'] <=> $a['count']);

        return $stats;
    }
}
