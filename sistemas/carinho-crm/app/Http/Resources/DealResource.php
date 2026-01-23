<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'stage' => [
                'id' => $this->stage_id,
                'name' => $this->whenLoaded('stage', fn() => $this->stage->name),
                'order' => $this->whenLoaded('stage', fn() => $this->stage->stage_order),
            ],
            'value_estimated' => (float) $this->value_estimated,
            'value_formatted' => 'R$ ' . number_format($this->value_estimated, 2, ',', '.'),
            'status' => [
                'id' => $this->status_id,
                'code' => $this->whenLoaded('status', fn() => $this->status->code),
                'label' => $this->whenLoaded('status', fn() => $this->status->label),
            ],
            'is_open' => $this->isOpen(),
            'is_won' => $this->isWon(),
            'is_lost' => $this->isLost(),
            'days_in_stage' => $this->getDaysInCurrentStage(),
            'total_days_in_pipeline' => $this->getTotalDaysInPipeline(),
            'lead' => new LeadResource($this->whenLoaded('lead')),
            'proposals' => ProposalResource::collection($this->whenLoaded('proposals')),
            'latest_proposal' => new ProposalResource($this->whenLoaded('proposals', fn() => $this->getLatestProposal())),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
