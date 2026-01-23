<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notificacao enviada ao cliente.
 *
 * @property int $id
 * @property int $client_id
 * @property ?int $schedule_id
 * @property string $notif_type
 * @property int $status_id
 * @property ?string $sent_at
 */
class Notification extends Model
{
    protected $table = 'notifications';
    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'schedule_id',
        'notif_type',
        'status_id',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Tipos de notificacao.
     */
    public const TYPE_SERVICE_START = 'service_start';
    public const TYPE_SERVICE_END = 'service_end';
    public const TYPE_CAREGIVER_ASSIGNED = 'caregiver_assigned';
    public const TYPE_CAREGIVER_REPLACED = 'caregiver_replaced';
    public const TYPE_SCHEDULE_REMINDER = 'schedule_reminder';
    public const TYPE_CANCELLATION = 'cancellation';
    public const TYPE_EMERGENCY_ALERT = 'emergency_alert';

    /**
     * Agendamento associado.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    /**
     * Status da notificacao.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainNotificationStatus::class, 'status_id');
    }

    /**
     * Verifica se foi enviada.
     */
    public function isSent(): bool
    {
        return $this->status_id === DomainNotificationStatus::SENT;
    }

    /**
     * Verifica se falhou.
     */
    public function isFailed(): bool
    {
        return $this->status_id === DomainNotificationStatus::FAILED;
    }

    /**
     * Verifica se esta na fila.
     */
    public function isQueued(): bool
    {
        return $this->status_id === DomainNotificationStatus::QUEUED;
    }

    /**
     * Marca como enviada.
     */
    public function markAsSent(): self
    {
        $this->status_id = DomainNotificationStatus::SENT;
        $this->sent_at = now();
        $this->save();

        return $this;
    }

    /**
     * Marca como falhou.
     */
    public function markAsFailed(): self
    {
        $this->status_id = DomainNotificationStatus::FAILED;
        $this->save();

        return $this;
    }

    /**
     * Retorna tipos de notificacao disponiveis.
     */
    public static function types(): array
    {
        return [
            self::TYPE_SERVICE_START => 'Inicio do servico',
            self::TYPE_SERVICE_END => 'Fim do servico',
            self::TYPE_CAREGIVER_ASSIGNED => 'Cuidador alocado',
            self::TYPE_CAREGIVER_REPLACED => 'Cuidador substituido',
            self::TYPE_SCHEDULE_REMINDER => 'Lembrete de agenda',
            self::TYPE_CANCELLATION => 'Cancelamento',
            self::TYPE_EMERGENCY_ALERT => 'Alerta de emergencia',
        ];
    }

    /**
     * Scope por cliente.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope por tipo.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('notif_type', $type);
    }

    /**
     * Scope para enviadas.
     */
    public function scopeSent($query)
    {
        return $query->where('status_id', DomainNotificationStatus::SENT);
    }

    /**
     * Scope para pendentes (na fila).
     */
    public function scopePending($query)
    {
        return $query->where('status_id', DomainNotificationStatus::QUEUED);
    }
}
