<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    public $timestamps = false;

    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id',
        'service_id',
        'service_date',
        'description',
        'qty',
        'unit_price',
        'amount',
        'caregiver_id',
        'service_type_id',
    ];

    protected $casts = [
        'service_date' => 'date',
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    /**
     * Boot do model para calcular amount automaticamente.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            if ($item->qty && $item->unit_price) {
                $item->amount = $item->qty * $item->unit_price;
            }
        });
    }

    /**
     * Relacionamento com a fatura.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Relacionamento com tipo de serviço.
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(DomainServiceType::class, 'service_type_id');
    }

    /**
     * Calcula o valor da comissão do cuidador para este item.
     */
    public function getCaregiverCommissionAmount(): float
    {
        $percent = $this->serviceType?->getCaregiverCommissionPercent() 
            ?? config('financeiro.commission.caregiver_percent', 70);

        return round($this->amount * ($percent / 100), 2);
    }

    /**
     * Calcula o valor da margem da empresa para este item.
     */
    public function getCompanyMarginAmount(): float
    {
        return $this->amount - $this->getCaregiverCommissionAmount();
    }

    /**
     * Formata descrição detalhada.
     */
    public function getDetailedDescriptionAttribute(): string
    {
        $date = $this->service_date?->format('d/m/Y') ?? '';
        return "{$this->description} - {$date} ({$this->qty}h)";
    }
}
