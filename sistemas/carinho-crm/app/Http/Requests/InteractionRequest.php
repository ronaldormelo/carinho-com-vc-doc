<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InteractionRequest extends FormRequest
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
        return [
            'lead_id' => 'required|exists:leads,id',
            'channel_id' => 'required|exists:domain_interaction_channel,id',
            'summary' => 'required|string|max:5000',
            'occurred_at' => 'nullable|date',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'lead_id' => 'lead',
            'channel_id' => 'canal',
            'summary' => 'resumo',
            'occurred_at' => 'data da interação',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('occurred_at')) {
            $this->merge([
                'occurred_at' => now(),
            ]);
        }
    }
}
