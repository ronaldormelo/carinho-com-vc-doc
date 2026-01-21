<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'proposal_id' => $this->proposal_id,
            'status' => [
                'id' => $this->status_id,
                'code' => $this->whenLoaded('status', fn() => $this->status->code),
                'label' => $this->whenLoaded('status', fn() => $this->status->label),
            ],
            'signed_at' => $this->signed_at?->toIso8601String(),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'is_draft' => $this->isDraft(),
            'is_signed' => $this->isSigned(),
            'is_active' => $this->isActive(),
            'is_closed' => $this->isClosed(),
            'is_vigente' => $this->isVigente(),
            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'days_until_expiration' => $this->getDaysUntilExpiration(),
            'duration_days' => $this->getDurationInDays(),
            'monthly_value' => $this->monthly_value,
            'total_value' => $this->total_value,
            'client' => new ClientResource($this->whenLoaded('client')),
            'proposal' => new ProposalResource($this->whenLoaded('proposal')),
        ];
    }
}
