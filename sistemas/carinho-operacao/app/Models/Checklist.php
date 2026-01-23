<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Checklist de inicio ou fim de atendimento.
 *
 * @property int $id
 * @property int $service_request_id
 * @property int $checklist_type_id
 * @property array $template_json
 */
class Checklist extends Model
{
    protected $table = 'checklists';
    public $timestamps = false;

    protected $fillable = [
        'service_request_id',
        'checklist_type_id',
        'template_json',
    ];

    protected $casts = [
        'template_json' => 'array',
    ];

    /**
     * Solicitacao de servico.
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    /**
     * Tipo de checklist.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DomainChecklistType::class, 'checklist_type_id');
    }

    /**
     * Entradas/itens do checklist.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(ChecklistEntry::class, 'checklist_id');
    }

    /**
     * Verifica se e de inicio.
     */
    public function isStart(): bool
    {
        return $this->checklist_type_id === DomainChecklistType::START;
    }

    /**
     * Verifica se e de fim.
     */
    public function isEnd(): bool
    {
        return $this->checklist_type_id === DomainChecklistType::END;
    }

    /**
     * Calcula percentual de conclusao.
     */
    public function getCompletionPercentAttribute(): int
    {
        $total = $this->entries()->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $this->entries()->where('completed', true)->count();

        return (int) round(($completed / $total) * 100);
    }

    /**
     * Verifica se todos os itens foram completados.
     */
    public function isComplete(): bool
    {
        return $this->entries()->where('completed', false)->doesntExist();
    }

    /**
     * Scope para checklists de inicio.
     */
    public function scopeStart($query)
    {
        return $query->where('checklist_type_id', DomainChecklistType::START);
    }

    /**
     * Scope para checklists de fim.
     */
    public function scopeEnd($query)
    {
        return $query->where('checklist_type_id', DomainChecklistType::END);
    }
}
