<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'invoice_item_id' => $this->invoice_item_id,
            'service_date' => $this->service_date?->toDateString(),
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'commission_percent' => (float) $this->commission_percent,
            'net_amount' => $this->net_amount,
            'company_share' => $this->company_share,
        ];
    }
}
