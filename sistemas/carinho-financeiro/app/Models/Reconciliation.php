<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Reconciliation extends Model
{
    use LogsActivity;

    public $timestamps = false;

    protected $table = 'reconciliations';

    protected $fillable = [
        'period',
        'status_id',
        'notes',
        'total_invoiced',
        'total_received',
        'total_payouts',
        'total_fees',
        'balance',
        'discrepancy_amount',
        'started_at',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'total_invoiced' => 'decimal:2',
        'total_received' => 'decimal:2',
        'total_payouts' => 'decimal:2',
        'total_fees' => 'decimal:2',
        'balance' => 'decimal:2',
        'discrepancy_amount' => 'decimal:2',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainReconciliationStatus::class, 'status_id');
    }

    /**
     * Verifica se está aberta.
     */
    public function isOpen(): bool
    {
        return $this->status_id === DomainReconciliationStatus::OPEN;
    }

    /**
     * Verifica se está fechada.
     */
    public function isClosed(): bool
    {
        return $this->status_id === DomainReconciliationStatus::CLOSED;
    }

    /**
     * Fecha a conciliação.
     */
    public function close(?string $closedBy = null): self
    {
        $this->status_id = DomainReconciliationStatus::CLOSED;
        $this->closed_at = now();
        $this->closed_by = $closedBy;
        $this->save();
        return $this;
    }

    /**
     * Verifica se há discrepância.
     */
    public function hasDiscrepancy(): bool
    {
        return abs($this->discrepancy_amount ?? 0) > 0.01;
    }

    /**
     * Calcula o saldo.
     */
    public function calculateBalance(): self
    {
        $this->balance = ($this->total_received ?? 0) 
            - ($this->total_payouts ?? 0) 
            - ($this->total_fees ?? 0);
        
        $this->discrepancy_amount = ($this->total_invoiced ?? 0) - ($this->total_received ?? 0);
        
        $this->save();
        return $this;
    }

    /**
     * Scope para conciliações abertas.
     */
    public function scopeOpen($query)
    {
        return $query->where('status_id', DomainReconciliationStatus::OPEN);
    }

    /**
     * Scope para conciliações fechadas.
     */
    public function scopeClosed($query)
    {
        return $query->where('status_id', DomainReconciliationStatus::CLOSED);
    }

    /**
     * Scope por período.
     */
    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Configurações de log de atividade.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status_id', 'balance', 'discrepancy_amount', 'closed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
