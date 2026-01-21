<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProposalRequest extends FormRequest
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
            'deal_id' => 'required|exists:deals,id',
            'service_type_id' => 'required|exists:domain_service_type,id',
            'price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'expires_at' => 'nullable|date|after:today',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'deal_id' => 'negócio',
            'service_type_id' => 'tipo de serviço',
            'price' => 'preço',
            'notes' => 'observações',
            'expires_at' => 'data de expiração',
        ];
    }
}
