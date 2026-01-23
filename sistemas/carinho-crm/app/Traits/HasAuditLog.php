<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Trait para auditoria de alterações (LGPD compliance)
 */
trait HasAuditLog
{
    use LogsActivity;

    /**
     * Configurar opções de log de atividade
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->getAuditedFields())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => $this->getAuditDescription($eventName))
            ->useLogName($this->getLogName());
    }

    /**
     * Campos a serem auditados (sobrescrever no Model se necessário)
     */
    protected function getAuditedFields(): array
    {
        return $this->audited ?? ['*'];
    }

    /**
     * Nome do log (sobrescrever no Model se necessário)
     */
    protected function getLogName(): string
    {
        return $this->logName ?? 'default';
    }

    /**
     * Descrição do evento de auditoria
     */
    protected function getAuditDescription(string $eventName): string
    {
        $modelName = class_basename($this);
        $identifier = $this->getKey() ?? 'novo';

        return match($eventName) {
            'created' => "{$modelName} #{$identifier} criado",
            'updated' => "{$modelName} #{$identifier} atualizado",
            'deleted' => "{$modelName} #{$identifier} excluído",
            default => "{$modelName} #{$identifier}: {$eventName}",
        };
    }

    /**
     * Obter histórico de alterações do modelo
     */
    public function getAuditHistory()
    {
        return $this->activities()->orderBy('created_at', 'desc')->get();
    }
}
