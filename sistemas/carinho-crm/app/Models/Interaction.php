<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Domain\DomainInteractionChannel;

class Interaction extends Model
{
    use HasFactory;

    protected $table = 'interactions';
    public $timestamps = false;

    protected $fillable = [
        'lead_id',
        'channel_id',
        'summary',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    // Relacionamentos
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function channel()
    {
        return $this->belongsTo(DomainInteractionChannel::class, 'channel_id');
    }

    // Scopes
    public function scopeByChannel($query, int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    public function scopeWhatsApp($query)
    {
        return $query->where('channel_id', DomainInteractionChannel::WHATSAPP);
    }

    public function scopeEmail($query)
    {
        return $query->where('channel_id', DomainInteractionChannel::EMAIL);
    }

    public function scopePhone($query)
    {
        return $query->where('channel_id', DomainInteractionChannel::PHONE);
    }

    public function scopeForLead($query, int $leadId)
    {
        return $query->where('lead_id', $leadId);
    }

    public function scopeOccurredBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('occurred_at', 'desc');
    }

    // Métodos de negócio
    public function isWhatsApp(): bool
    {
        return $this->channel_id === DomainInteractionChannel::WHATSAPP;
    }

    public function isEmail(): bool
    {
        return $this->channel_id === DomainInteractionChannel::EMAIL;
    }

    public function isPhone(): bool
    {
        return $this->channel_id === DomainInteractionChannel::PHONE;
    }

    public function getDaysAgo(): int
    {
        return $this->occurred_at->diffInDays(now());
    }

    /**
     * Obter resumo truncado
     */
    public function getTruncatedSummaryAttribute(int $length = 100): string
    {
        return strlen($this->summary) > $length
            ? substr($this->summary, 0, $length) . '...'
            : $this->summary;
    }

    /**
     * Obter ícone do canal
     */
    public function getChannelIconAttribute(): string
    {
        return match($this->channel_id) {
            DomainInteractionChannel::WHATSAPP => 'whatsapp',
            DomainInteractionChannel::EMAIL => 'envelope',
            DomainInteractionChannel::PHONE => 'phone',
            default => 'comment',
        };
    }
}
