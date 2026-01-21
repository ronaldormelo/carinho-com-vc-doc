<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deal_id' => $this->deal_id,
            'service_type' => [
                'id' => $this->service_type_id,
                'code' => $this->whenLoaded('serviceType', fn() => $this->serviceType->code),
                'label' => $this->whenLoaded('serviceType', fn() => $this->serviceType->label),
            ],
            'price' => (float) $this->price,
            'price_formatted' => $this->formatted_price,
            'notes' => $this->notes,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_expired' => $this->isExpired(),
            'is_valid' => $this->isValid(),
            'days_until_expiration' => $this->getDaysUntilExpiration(),
            'has_contract' => $this->hasContract(),
            'deal' => new DealResource($this->whenLoaded('deal')),
            'contract' => new ContractResource($this->whenLoaded('contract')),
        ];
    }
}
