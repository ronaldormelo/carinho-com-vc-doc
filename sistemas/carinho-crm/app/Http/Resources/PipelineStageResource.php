<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PipelineStageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'stage_order' => $this->stage_order,
            'active' => $this->active,
            'is_first_stage' => $this->isFirstStage(),
            'is_last_stage' => $this->isLastStage(),
            'deals_count' => $this->getDealsCount(),
            'deals_value' => $this->getDealsValue(),
            'deals' => DealResource::collection($this->whenLoaded('deals')),
        ];
    }
}
