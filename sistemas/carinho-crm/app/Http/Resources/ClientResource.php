<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'primary_contact' => $this->primary_contact,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'preferences' => $this->preferences_json,
            'display_name' => $this->display_name,
            'has_active_contract' => $this->hasActiveContract(),
            'lead' => new LeadResource($this->whenLoaded('lead')),
            'care_needs' => CareNeedResource::collection($this->whenLoaded('careNeeds')),
            'contracts' => ContractResource::collection($this->whenLoaded('contracts')),
            'active_contract' => new ContractResource($this->whenLoaded('contracts', fn() => $this->getActiveContract())),
            'consents' => ConsentResource::collection($this->whenLoaded('consents')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
