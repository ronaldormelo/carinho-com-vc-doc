<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'city' => $this->city,
            'urgency' => [
                'id' => $this->urgency_id,
                'code' => $this->whenLoaded('urgency', fn() => $this->urgency->code),
                'label' => $this->whenLoaded('urgency', fn() => $this->urgency->label),
            ],
            'service_type' => [
                'id' => $this->service_type_id,
                'code' => $this->whenLoaded('serviceType', fn() => $this->serviceType->code),
                'label' => $this->whenLoaded('serviceType', fn() => $this->serviceType->label),
            ],
            'source' => $this->source,
            'status' => [
                'id' => $this->status_id,
                'code' => $this->whenLoaded('status', fn() => $this->status->code),
                'label' => $this->whenLoaded('status', fn() => $this->status->label),
            ],
            'utm_id' => $this->utm_id,
            'is_in_pipeline' => $this->isInPipeline(),
            'is_converted' => $this->isConverted(),
            'is_lost' => $this->isLost(),
            'days_since_last_contact' => $this->getDaysSinceLastContact(),
            'client' => new ClientResource($this->whenLoaded('client')),
            'deals' => DealResource::collection($this->whenLoaded('deals')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'interactions_count' => $this->whenCounted('interactions'),
            'last_interaction' => new InteractionResource($this->whenLoaded('interactions', fn() => $this->getLastInteraction())),
            'loss_reason' => new LossReasonResource($this->whenLoaded('lossReason')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
