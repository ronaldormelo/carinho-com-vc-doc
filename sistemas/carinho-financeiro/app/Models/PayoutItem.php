<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutItem extends Model
{
    public $timestamps = false;

    protected $table = 'payout_items';

    protected $fillable = [
        'payout_id',
        'service_id',
        'invoice_item_id',
        'amount',
        'commission_percent',
        'service_date',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'service_date' => 'date',
    ];

    /**
     * Relacionamento com o repasse.
     */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class, 'payout_id');
    }

    /**
     * Calcula o valor líquido após comissão.
     */
    public function getNetAmountAttribute(): float
    {
        return round($this->amount * ($this->commission_percent / 100), 2);
    }

    /**
     * Calcula o valor da comissão da empresa.
     */
    public function getCompanyShareAttribute(): float
    {
        return $this->amount - $this->net_amount;
    }
}
