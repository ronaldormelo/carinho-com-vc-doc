<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Solicitacao de dados LGPD.
 *
 * @property int $id
 * @property int $subject_type_id
 * @property int $subject_id
 * @property int $request_type_id
 * @property int $status_id
 * @property Carbon $requested_at
 * @property Carbon|null $resolved_at
 */
class DataRequest extends Model
{
    protected $table = 'data_requests';

    public $timestamps = false;

    protected $fillable = [
        'subject_type_id',
        'subject_id',
        'request_type_id',
        'status_id',
        'requested_at',
        'resolved_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function subjectType(): BelongsTo
    {
        return $this->belongsTo(DomainConsentSubjectType::class, 'subject_type_id');
    }

    public function requestType(): BelongsTo
    {
        return $this->belongsTo(DomainRequestType::class, 'request_type_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainRequestStatus::class, 'status_id');
    }

    /**
     * Verifica se a solicitacao esta aberta.
     */
    public function isOpen(): bool
    {
        return $this->status_id === DomainRequestStatus::OPEN;
    }

    /**
     * Verifica se a solicitacao esta em progresso.
     */
    public function isInProgress(): bool
    {
        return $this->status_id === DomainRequestStatus::IN_PROGRESS;
    }

    /**
     * Verifica se a solicitacao foi concluida.
     */
    public function isDone(): bool
    {
        return $this->status_id === DomainRequestStatus::DONE;
    }

    /**
     * Verifica se a solicitacao foi rejeitada.
     */
    public function isRejected(): bool
    {
        return $this->status_id === DomainRequestStatus::REJECTED;
    }

    /**
     * Marca como em progresso.
     */
    public function markAsInProgress(): bool
    {
        $this->status_id = DomainRequestStatus::IN_PROGRESS;

        return $this->save();
    }

    /**
     * Marca como concluida.
     */
    public function markAsDone(): bool
    {
        $this->status_id = DomainRequestStatus::DONE;
        $this->resolved_at = now();

        return $this->save();
    }

    /**
     * Marca como rejeitada.
     */
    public function markAsRejected(): bool
    {
        $this->status_id = DomainRequestStatus::REJECTED;
        $this->resolved_at = now();

        return $this->save();
    }

    /**
     * Verifica se o prazo expirou (15 dias conforme LGPD).
     */
    public function isOverdue(): bool
    {
        $deadlineDays = config('documentos.retention.request_deadline_days', 15);

        return !$this->isDone()
            && !$this->isRejected()
            && $this->requested_at->addDays($deadlineDays)->isPast();
    }

    /**
     * Calcula dias restantes para o prazo.
     */
    public function daysUntilDeadline(): int
    {
        $deadlineDays = config('documentos.retention.request_deadline_days', 15);
        $deadline = $this->requested_at->addDays($deadlineDays);

        return max(0, now()->diffInDays($deadline, false));
    }

    /**
     * Cria solicitacao de exportacao.
     */
    public static function createExportRequest(int $subjectTypeId, int $subjectId): self
    {
        return static::create([
            'subject_type_id' => $subjectTypeId,
            'subject_id' => $subjectId,
            'request_type_id' => DomainRequestType::EXPORT,
            'status_id' => DomainRequestStatus::OPEN,
            'requested_at' => now(),
        ]);
    }

    /**
     * Cria solicitacao de exclusao.
     */
    public static function createDeleteRequest(int $subjectTypeId, int $subjectId): self
    {
        return static::create([
            'subject_type_id' => $subjectTypeId,
            'subject_id' => $subjectId,
            'request_type_id' => DomainRequestType::DELETE,
            'status_id' => DomainRequestStatus::OPEN,
            'requested_at' => now(),
        ]);
    }

    /**
     * Scope para solicitacoes pendentes.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status_id', [
            DomainRequestStatus::OPEN,
            DomainRequestStatus::IN_PROGRESS,
        ]);
    }

    /**
     * Scope para solicitacoes vencidas.
     */
    public function scopeOverdue($query)
    {
        $deadlineDays = config('documentos.retention.request_deadline_days', 15);

        return $query->pending()
            ->where('requested_at', '<', now()->subDays($deadlineDays));
    }
}
