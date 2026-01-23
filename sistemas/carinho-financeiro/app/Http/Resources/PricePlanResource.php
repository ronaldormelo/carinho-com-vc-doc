<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricePlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'service_type' => $this->whenLoaded('serviceType', function () {
                return [
                    'id' => $this->serviceType->id,
                    'code' => $this->serviceType->code,
                    'label' => $this->serviceType->label,
                ];
            }),
            'base_price' => (float) $this->base_price,
            'description' => $this->description,
            'min_hours' => $this->min_hours,
            'max_hours' => $this->max_hours,
            'region_code' => $this->region_code,
            'active' => (bool) $this->active,
            'rules' => $this->whenLoaded('rules', function () {
                return $this->rules->map(function ($rule) {
                    return [
                        'id' => $rule->id,
                        'name' => $rule->name,
                        'rule_type' => $rule->rule_type,
                        'value' => (float) $rule->value,
                        'conditions' => $rule->conditions_json,
                        'priority' => $rule->priority,
                    ];
                });
            }),
            'meets_minimum_viable' => $this->meetsMinimumViable(),
        ];
    }
}
