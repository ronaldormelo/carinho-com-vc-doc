<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FiscalDocument extends Model
{
    use LogsActivity;

    public $timestamps = false;

    protected $table = 'fiscal_documents';

    protected $fillable = [
        'invoice_id',
        'doc_number',
        'issued_at',
        'file_url',
        'doc_type',
        'status',
        'amount',
        'tax_amount',
        'service_code',
        'description',
        'external_id',
        'verification_code',
        'canceled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'canceled_at' => 'datetime',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    /**
     * Status possíveis.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_ERROR = 'error';

    /**
     * Relacionamento com a fatura.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Verifica se foi emitida.
     */
    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    /**
     * Verifica se foi cancelada.
     */
    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    /**
     * Verifica se pode ser cancelada.
     */
    public function canBeCanceled(): bool
    {
        if (!$this->isIssued()) {
            return false;
        }

        // Geralmente NFS-e pode ser cancelada em até 24-72h após emissão
        // Depende do município
        return $this->issued_at->diffInHours(now()) <= 48;
    }

    /**
     * Marca como emitida.
     */
    public function markAsIssued(string $docNumber, string $verificationCode): self
    {
        $this->status = self::STATUS_ISSUED;
        $this->doc_number = $docNumber;
        $this->verification_code = $verificationCode;
        $this->issued_at = now();
        $this->save();
        return $this;
    }

    /**
     * Marca como cancelada.
     */
    public function markAsCanceled(string $reason): self
    {
        $this->status = self::STATUS_CANCELED;
        $this->canceled_at = now();
        $this->cancel_reason = $reason;
        $this->save();
        return $this;
    }

    /**
     * Scope para documentos emitidos.
     */
    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    /**
     * Scope para documentos por período.
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('issued_at', [$startDate, $endDate]);
    }

    /**
     * Configurações de log de atividade.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'doc_number', 'canceled_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
