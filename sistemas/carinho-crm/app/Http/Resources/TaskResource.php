<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'assigned_to' => $this->assigned_to,
            'assignee' => $this->whenLoaded('assignee', fn() => [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
                'email' => $this->assignee->email,
            ]),
            'due_at' => $this->due_at?->toIso8601String(),
            'status' => [
                'id' => $this->status_id,
                'code' => $this->whenLoaded('status', fn() => $this->status->code),
                'label' => $this->whenLoaded('status', fn() => $this->status->label),
            ],
            'notes' => $this->notes,
            'is_open' => $this->isOpen(),
            'is_done' => $this->isDone(),
            'is_canceled' => $this->isCanceled(),
            'is_overdue' => $this->isOverdue(),
            'is_due_today' => $this->isDueToday(),
            'is_assigned' => $this->isAssigned(),
            'days_until_due' => $this->getDaysUntilDue(),
            'priority' => $this->priority,
            'lead' => new LeadResource($this->whenLoaded('lead')),
        ];
    }
}
