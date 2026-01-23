<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'consent_type' => $this->consent_type,
            'consent_type_label' => $this->getTypeLabel(),
            'granted_at' => $this->granted_at?->toIso8601String(),
            'source' => $this->source,
            'source_label' => $this->getSourceLabel(),
        ];
    }
}
