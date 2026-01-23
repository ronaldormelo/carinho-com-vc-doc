<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'service_date' => $this->service_date?->toDateString(),
            'description' => $this->description,
            'qty' => (float) $this->qty,
            'unit_price' => (float) $this->unit_price,
            'amount' => (float) $this->amount,
            'caregiver_id' => $this->caregiver_id,
            'service_type' => $this->whenLoaded('serviceType', function () {
                return $this->serviceType ? [
                    'id' => $this->serviceType->id,
                    'code' => $this->serviceType->code,
                    'label' => $this->serviceType->label,
                ] : null;
            }),
            'caregiver_commission' => $this->getCaregiverCommissionAmount(),
            'company_margin' => $this->getCompanyMarginAmount(),
        ];
    }
}
