<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractRequest extends FormRequest
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
            'client_id' => 'required|exists:clients,id',
            'proposal_id' => 'required|exists:proposals,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['status_id'] = 'sometimes|exists:domain_contract_status,id';
            $rules['signed_at'] = 'nullable|date';
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'cliente',
            'proposal_id' => 'proposta',
            'status_id' => 'status',
            'signed_at' => 'data de assinatura',
            'start_date' => 'data de início',
            'end_date' => 'data de término',
        ];
    }
}
