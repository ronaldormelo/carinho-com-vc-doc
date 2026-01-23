<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CareNeedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'patient_type' => [
                'id' => $this->patient_type_id,
                'code' => $this->whenLoaded('patientType', fn() => $this->patientType->code),
                'label' => $this->whenLoaded('patientType', fn() => $this->patientType->label),
            ],
            'conditions' => $this->conditions_json ?? [],
            'notes' => $this->notes,
        ];
    }
}
