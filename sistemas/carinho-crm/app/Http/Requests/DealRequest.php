<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DealRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'lead_id' => 'required|exists:leads,id',
            'stage_id' => 'required|exists:pipeline_stages,id',
            'value_estimated' => 'nullable|numeric|min:0',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['status_id'] = 'sometimes|exists:domain_deal_status,id';
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'lead_id' => 'lead',
            'stage_id' => 'estágio',
            'value_estimated' => 'valor estimado',
            'status_id' => 'status',
        ];
    }
}
