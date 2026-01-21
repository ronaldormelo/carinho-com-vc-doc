<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InteractionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'channel' => [
                'id' => $this->channel_id,
                'code' => $this->whenLoaded('channel', fn() => $this->channel->code),
                'label' => $this->whenLoaded('channel', fn() => $this->channel->label),
                'icon' => $this->channel_icon,
            ],
            'summary' => $this->summary,
            'summary_truncated' => $this->truncated_summary,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'days_ago' => $this->getDaysAgo(),
            'is_whatsapp' => $this->isWhatsApp(),
            'is_email' => $this->isEmail(),
            'is_phone' => $this->isPhone(),
            'lead' => new LeadResource($this->whenLoaded('lead')),
        ];
    }
}
