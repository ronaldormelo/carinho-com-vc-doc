<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|integer',
            'contract_id' => 'required|integer',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'external_reference' => 'nullable|string|max:128',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:255',
            'items.*.qty' => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.service_date' => 'nullable|date',
            'items.*.service_id' => 'nullable|integer',
            'items.*.caregiver_id' => 'nullable|integer',
            'items.*.service_type_id' => 'nullable|integer|exists:domain_service_type,id',
            'items.*.is_weekend' => 'nullable|boolean',
            'items.*.is_holiday' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'O cliente é obrigatório',
            'contract_id.required' => 'O contrato é obrigatório',
            'period_end.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial',
            'items.*.description.required_with' => 'A descrição do item é obrigatória',
            'items.*.qty.required_with' => 'A quantidade é obrigatória',
            'items.*.qty.min' => 'A quantidade mínima é 0.01',
        ];
    }
}
