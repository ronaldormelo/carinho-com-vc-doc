<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Domain\DomainTaskStatus;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tasks';
    public $timestamps = false;

    protected $fillable = [
        'lead_id',
        'assigned_to',
        'due_at',
        'status_id',
        'notes',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    // Relacionamentos
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function status()
    {
        return $this->belongsTo(DomainTaskStatus::class, 'status_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status_id', DomainTaskStatus::OPEN);
    }

    public function scopeDone($query)
    {
        return $query->where('status_id', DomainTaskStatus::DONE);
    }

    public function scopeCanceled($query)
    {
        return $query->where('status_id', DomainTaskStatus::CANCELED);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_at', '<', now())
                     ->where('status_id', DomainTaskStatus::OPEN);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_at', today())
                     ->where('status_id', DomainTaskStatus::OPEN);
    }

    public function scopeDueSoon($query, int $days = 3)
    {
        return $query->where('due_at', '<=', now()->addDays($days))
                     ->where('due_at', '>=', now())
                     ->where('status_id', DomainTaskStatus::OPEN);
    }

    public function scopeForLead($query, int $leadId)
    {
        return $query->where('lead_id', $leadId);
    }

    // Métodos de negócio
    public function isOpen(): bool
    {
        return $this->status_id === DomainTaskStatus::OPEN;
    }

    public function isDone(): bool
    {
        return $this->status_id === DomainTaskStatus::DONE;
    }

    public function isCanceled(): bool
    {
        return $this->status_id === DomainTaskStatus::CANCELED;
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_at !== null && $this->due_at->isPast();
    }

    public function isDueToday(): bool
    {
        return $this->isOpen() && $this->due_at !== null && $this->due_at->isToday();
    }

    public function isAssigned(): bool
    {
        return $this->assigned_to !== null;
    }

    public function markAsDone(): bool
    {
        if (!$this->isOpen()) {
            return false;
        }

        $this->status_id = DomainTaskStatus::DONE;
        return $this->save();
    }

    public function cancel(): bool
    {
        if (!$this->isOpen()) {
            return false;
        }

        $this->status_id = DomainTaskStatus::CANCELED;
        return $this->save();
    }

    public function assignTo(int $userId): bool
    {
        $this->assigned_to = $userId;
        return $this->save();
    }

    public function unassign(): bool
    {
        $this->assigned_to = null;
        return $this->save();
    }

    public function getDaysUntilDue(): ?int
    {
        if ($this->due_at === null) {
            return null;
        }

        if ($this->isOverdue()) {
            return -1 * $this->due_at->diffInDays(now());
        }

        return now()->diffInDays($this->due_at);
    }

    /**
     * Obter prioridade baseada na data de vencimento
     */
    public function getPriorityAttribute(): string
    {
        if ($this->isOverdue()) {
            return 'critical';
        }

        if ($this->isDueToday()) {
            return 'high';
        }

        $daysUntilDue = $this->getDaysUntilDue();
        
        if ($daysUntilDue !== null && $daysUntilDue <= 3) {
            return 'medium';
        }

        return 'low';
    }
}
