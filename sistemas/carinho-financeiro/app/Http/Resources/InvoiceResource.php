<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'contract_id' => $this->contract_id,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'status' => [
                'id' => $this->status_id,
                'code' => $this->whenLoaded('status', fn () => $this->status->code),
                'label' => $this->whenLoaded('status', fn () => $this->status->label),
            ],
            'total_amount' => (float) $this->total_amount,
            'total_with_fees' => $this->total_with_fees,
            'discount_amount' => (float) ($this->discount_amount ?? 0),
            'cancellation_fee' => (float) ($this->cancellation_fee ?? 0),
            'notes' => $this->notes,
            'external_reference' => $this->external_reference,
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'fiscal_document' => $this->whenLoaded('fiscalDocument', function () {
                return $this->fiscalDocument ? [
                    'id' => $this->fiscalDocument->id,
                    'doc_number' => $this->fiscalDocument->doc_number,
                    'issued_at' => $this->fiscalDocument->issued_at?->toIso8601String(),
                    'status' => $this->fiscalDocument->status,
                ] : null;
            }),
            'can_be_paid' => $this->canBePaid(),
            'can_be_canceled' => $this->canBeCanceled(),
            'is_overdue' => $this->isOverdue(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
