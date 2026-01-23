<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item/entrada de um checklist.
 *
 * @property int $id
 * @property int $checklist_id
 * @property string $item_key
 * @property bool $completed
 * @property ?string $notes
 */
class ChecklistEntry extends Model
{
    protected $table = 'checklist_entries';
    public $timestamps = false;

    protected $fillable = [
        'checklist_id',
        'item_key',
        'completed',
        'notes',
    ];

    protected $casts = [
        'completed' => 'boolean',
    ];

    /**
     * Checklist pai.
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class, 'checklist_id');
    }

    /**
     * Marca como completo.
     */
    public function markComplete(?string $notes = null): self
    {
        $this->completed = true;
        if ($notes !== null) {
            $this->notes = $notes;
        }
        $this->save();

        return $this;
    }

    /**
     * Marca como incompleto.
     */
    public function markIncomplete(): self
    {
        $this->completed = false;
        $this->save();

        return $this;
    }

    /**
     * Scope para itens completos.
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    /**
     * Scope para itens pendentes.
     */
    public function scopePending($query)
    {
        return $query->where('completed', false);
    }
}
