<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model para histórico de alterações em campanhas.
 * 
 * Registro de auditoria completo de todas as alterações
 * realizadas em campanhas, incluindo quem, quando e o quê.
 */
class CampaignAuditLog extends Model
{
    protected $table = 'campaign_audit_log';
    
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'user_id',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Ações disponíveis
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_ACTIVATED = 'activated';
    public const ACTION_PAUSED = 'paused';
    public const ACTION_FINISHED = 'finished';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';

    /**
     * Relacionamento com campanha.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Scope por campanha.
     */
    public function scopeByCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope por usuário.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope por período.
     */
    public function scopeInPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Registra criação de campanha.
     */
    public static function logCreation(
        int $campaignId,
        ?int $userId = null,
        ?array $requestInfo = null
    ): self {
        return self::create([
            'campaign_id' => $campaignId,
            'user_id' => $userId,
            'action' => self::ACTION_CREATED,
            'ip_address' => $requestInfo['ip'] ?? null,
            'user_agent' => $requestInfo['user_agent'] ?? null,
            'created_at' => now(),
        ]);
    }

    /**
     * Registra atualização de campo.
     */
    public static function logFieldUpdate(
        int $campaignId,
        string $fieldName,
        $oldValue,
        $newValue,
        ?int $userId = null,
        ?array $requestInfo = null
    ): self {
        return self::create([
            'campaign_id' => $campaignId,
            'user_id' => $userId,
            'action' => self::ACTION_UPDATED,
            'field_name' => $fieldName,
            'old_value' => is_array($oldValue) ? json_encode($oldValue) : (string) $oldValue,
            'new_value' => is_array($newValue) ? json_encode($newValue) : (string) $newValue,
            'ip_address' => $requestInfo['ip'] ?? null,
            'user_agent' => $requestInfo['user_agent'] ?? null,
            'created_at' => now(),
        ]);
    }

    /**
     * Registra mudança de status.
     */
    public static function logStatusChange(
        int $campaignId,
        string $action,
        ?int $userId = null,
        ?array $requestInfo = null
    ): self {
        return self::create([
            'campaign_id' => $campaignId,
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $requestInfo['ip'] ?? null,
            'user_agent' => $requestInfo['user_agent'] ?? null,
            'created_at' => now(),
        ]);
    }

    /**
     * Obtém histórico completo da campanha.
     */
    public static function getHistory(int $campaignId, int $limit = 50): array
    {
        return self::byCampaign($campaignId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Formata a descrição da alteração.
     */
    public function getDescription(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'Campanha criada',
            self::ACTION_ACTIVATED => 'Campanha ativada',
            self::ACTION_PAUSED => 'Campanha pausada',
            self::ACTION_FINISHED => 'Campanha finalizada',
            self::ACTION_APPROVED => 'Campanha aprovada',
            self::ACTION_REJECTED => 'Campanha rejeitada',
            self::ACTION_UPDATED => "Campo '{$this->field_name}' alterado de '{$this->old_value}' para '{$this->new_value}'",
            default => $this->action,
        };
    }
}
