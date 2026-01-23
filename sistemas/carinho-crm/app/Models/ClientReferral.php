<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Indicações de clientes (Programa de Referral)
 * 
 * Prática tradicional de aquisição de clientes por indicação:
 * - Rastreia quem indicou quem
 * - Acompanha conversão de indicações
 * - Base para programas de benefícios
 */
class ClientReferral extends Model
{
    use HasFactory;

    protected $table = 'client_referrals';

    protected $fillable = [
        'referrer_client_id',
        'referred_lead_id',
        'referred_client_id',
        'referred_name',
        'referred_phone',
        'status',
        'notes',
        'converted_at',
    ];

    protected $casts = [
        'converted_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constantes de status
    public const STATUS_PENDING = 'pending';       // Indicação registrada
    public const STATUS_CONTACTED = 'contacted';   // Indicado foi contatado
    public const STATUS_CONVERTED = 'converted';   // Indicado virou cliente
    public const STATUS_LOST = 'lost';             // Indicação não converteu

    // Relacionamentos
    public function referrer()
    {
        return $this->belongsTo(Client::class, 'referrer_client_id');
    }

    public function referredLead()
    {
        return $this->belongsTo(Lead::class, 'referred_lead_id');
    }

    public function referredClient()
    {
        return $this->belongsTo(Client::class, 'referred_client_id');
    }

    // Scopes
    public function scopeFromClient($query, int $clientId)
    {
        return $query->where('referrer_client_id', $clientId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeContacted($query)
    {
        return $query->where('status', self::STATUS_CONTACTED);
    }

    public function scopeConverted($query)
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    public function scopeLost($query)
    {
        return $query->where('status', self::STATUS_LOST);
    }

    public function scopeConvertedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('converted_at', [$startDate, $endDate]);
    }

    // Métodos de negócio
    
    /**
     * Verificar se está pendente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verificar se foi convertido
     */
    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    /**
     * Marcar como contatado
     */
    public function markAsContacted(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status = self::STATUS_CONTACTED;
        return $this->save();
    }

    /**
     * Registrar conversão
     */
    public function markAsConverted(int $clientId): bool
    {
        if ($this->status === self::STATUS_LOST) {
            return false;
        }

        $this->status = self::STATUS_CONVERTED;
        $this->referred_client_id = $clientId;
        $this->converted_at = now();
        return $this->save();
    }

    /**
     * Marcar como perdido
     */
    public function markAsLost(?string $reason = null): bool
    {
        if ($this->isConverted()) {
            return false;
        }

        $this->status = self::STATUS_LOST;
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Motivo: {$reason}";
        }
        return $this->save();
    }

    /**
     * Obter label do status
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_CONTACTED => 'Contatado',
            self::STATUS_CONVERTED => 'Convertido',
            self::STATUS_LOST => 'Perdido',
            default => 'Desconhecido',
        };
    }

    /**
     * Dias desde a indicação
     */
    public function getDaysSinceReferralAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Obter todas as indicações de um cliente com estatísticas
     */
    public static function getReferrerStats(int $clientId): array
    {
        $referrals = self::fromClient($clientId)->get();

        return [
            'total' => $referrals->count(),
            'pending' => $referrals->where('status', self::STATUS_PENDING)->count(),
            'contacted' => $referrals->where('status', self::STATUS_CONTACTED)->count(),
            'converted' => $referrals->where('status', self::STATUS_CONVERTED)->count(),
            'lost' => $referrals->where('status', self::STATUS_LOST)->count(),
            'conversion_rate' => $referrals->count() > 0
                ? round(($referrals->where('status', self::STATUS_CONVERTED)->count() / $referrals->count()) * 100, 1)
                : 0,
        ];
    }
}
