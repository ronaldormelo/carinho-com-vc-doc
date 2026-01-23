<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'caregiver_id' => $this->caregiver_id,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'status' => [
                'id' => $this->status_id,
                'code' => $this->whenLoaded('status', fn () => $this->status->code),
                'label' => $this->whenLoaded('status', fn () => $this->status->label),
            ],
            'total_amount' => (float) $this->total_amount,
            'commission_total' => (float) ($this->commission_total ?? 0),
            'transfer_fee' => (float) ($this->transfer_fee ?? 0),
            'net_amount' => (float) ($this->net_amount ?? $this->total_amount),
            'items' => PayoutItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),
            'bank_account' => $this->whenLoaded('bankAccount', function () {
                return $this->bankAccount ? [
                    'id' => $this->bankAccount->id,
                    'bank_name' => $this->bankAccount->bank_name,
                    'masked_account' => $this->bankAccount->masked_account,
                ] : null;
            }),
            'can_be_processed' => $this->canBeProcessed(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'stripe_transfer_id' => $this->stripe_transfer_id,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
