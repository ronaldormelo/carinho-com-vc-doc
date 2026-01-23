<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'method' => [
                'id' => $this->method_id,
                'code' => $this->whenLoaded('method', fn () => $this->method->code),
                'label' => $this->whenLoaded('method', fn () => $this->method->label),
            ],
            'amount' => (float) $this->amount,
            'status' => [
                'id' => $this->status_id,
                'code' => $this->whenLoaded('status', fn () => $this->status->code),
                'label' => $this->whenLoaded('status', fn () => $this->status->label),
            ],
            'paid_at' => $this->paid_at?->toIso8601String(),
            'external_id' => $this->external_id,
            'pix_code' => $this->when($this->isPix() && $this->isPending(), $this->pix_code),
            'pix_qrcode_url' => $this->when($this->isPix() && $this->isPending(), $this->pix_qrcode_url),
            'boleto_url' => $this->when($this->isBoleto() && $this->isPending(), $this->boleto_url),
            'boleto_barcode' => $this->when($this->isBoleto() && $this->isPending(), $this->boleto_barcode),
            'refund_amount' => (float) ($this->refund_amount ?? 0),
            'refund_reason' => $this->refund_reason,
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'refundable_amount' => $this->refundable_amount,
            'can_be_refunded' => $this->canBeRefunded(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
