<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Domain\DomainServiceType;

class Proposal extends Model
{
    use HasFactory;

    protected $table = 'proposals';
    public $timestamps = false;

    protected $fillable = [
        'deal_id',
        'service_type_id',
        'price',
        'notes',
        'expires_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    // Relacionamentos
    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(DomainServiceType::class, 'service_type_id');
    }

    public function contract()
    {
        return $this->hasOne(Contract::class);
    }

    // Scopes
    public function scopeByServiceType($query, int $serviceTypeId)
    {
        return $query->where('service_type_id', $serviceTypeId);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>=', now());
        });
    }

    public function scopeWithMinPrice($query, float $minPrice)
    {
        return $query->where('price', '>=', $minPrice);
    }

    // Métodos de negócio
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    public function getDaysUntilExpiration(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->expires_at);
    }

    public function hasContract(): bool
    {
        return $this->contract !== null;
    }

    /**
     * Obter preço formatado em BRL
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }
}
