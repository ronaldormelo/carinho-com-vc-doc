<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Domain\DomainJobStatus;
use Carbon\Carbon;

/**
 * Job de sincronizacao entre sistemas.
 *
 * @property int $id
 * @property string $job_type
 * @property int $status_id
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $finished_at
 */
class SyncJob extends Model
{
    public $timestamps = false;

    protected $table = 'sync_jobs';

    protected $fillable = [
        'job_type',
        'status_id',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    // Tipos de jobs de sincronizacao
    public const TYPE_CRM_OPERACAO = 'sync.crm_operacao';
    public const TYPE_OPERACAO_FINANCEIRO = 'sync.operacao_financeiro';
    public const TYPE_CRM_FINANCEIRO = 'sync.crm_financeiro';
    public const TYPE_CUIDADORES_CRM = 'sync.cuidadores_crm';
    public const TYPE_FULL_SYNC = 'sync.full';

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainJobStatus::class, 'status_id');
    }

    /**
     * Cria novo job na fila.
     */
    public static function queue(string $type): self
    {
        return self::create([
            'job_type' => $type,
            'status_id' => DomainJobStatus::QUEUED,
        ]);
    }

    /**
     * Inicia execucao do job.
     */
    public function start(): void
    {
        $this->update([
            'status_id' => DomainJobStatus::RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Marca como concluido.
     */
    public function complete(): void
    {
        $this->update([
            'status_id' => DomainJobStatus::DONE,
            'finished_at' => now(),
        ]);
    }

    /**
     * Marca como falhou.
     */
    public function fail(): void
    {
        $this->update([
            'status_id' => DomainJobStatus::FAILED,
            'finished_at' => now(),
        ]);
    }

    /**
     * Calcula duracao em segundos.
     */
    public function getDurationInSeconds(): ?int
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        return $this->finished_at->diffInSeconds($this->started_at);
    }

    /**
     * Verifica se esta em execucao.
     */
    public function isRunning(): bool
    {
        return $this->status_id === DomainJobStatus::RUNNING;
    }

    /**
     * Verifica se esta na fila.
     */
    public function isQueued(): bool
    {
        return $this->status_id === DomainJobStatus::QUEUED;
    }

    /**
     * Verifica se completou.
     */
    public function isDone(): bool
    {
        return $this->status_id === DomainJobStatus::DONE;
    }

    /**
     * Verifica se falhou.
     */
    public function isFailed(): bool
    {
        return $this->status_id === DomainJobStatus::FAILED;
    }

    /**
     * Escopo para jobs na fila.
     */
    public function scopeQueued($query)
    {
        return $query->where('status_id', DomainJobStatus::QUEUED);
    }

    /**
     * Escopo para jobs em execucao.
     */
    public function scopeRunning($query)
    {
        return $query->where('status_id', DomainJobStatus::RUNNING);
    }

    /**
     * Verifica se existe job em execucao do mesmo tipo.
     */
    public static function hasRunningJob(string $type): bool
    {
        return self::running()->where('job_type', $type)->exists();
    }

    /**
     * Ultimo job do tipo.
     */
    public static function lastOfType(string $type): ?self
    {
        return self::where('job_type', $type)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Estatisticas de jobs.
     */
    public static function getStats(): array
    {
        return [
            'queued' => self::queued()->count(),
            'running' => self::running()->count(),
            'completed_today' => self::where('status_id', DomainJobStatus::DONE)
                ->whereDate('finished_at', today())
                ->count(),
            'failed_today' => self::where('status_id', DomainJobStatus::FAILED)
                ->whereDate('finished_at', today())
                ->count(),
        ];
    }
}
