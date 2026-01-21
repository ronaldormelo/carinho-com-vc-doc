<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Domain\DomainLeadStatus;

class LeadRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'email' => 'nullable|email|max:255',
            'city' => 'required|string|max:128',
            'urgency_id' => 'required|exists:domain_urgency_level,id',
            'service_type_id' => 'required|exists:domain_service_type,id',
            'source' => 'required|string|max:128',
            'utm_id' => 'nullable|integer',
        ];

        // Para atualização, status é permitido
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['status_id'] = 'sometimes|exists:domain_lead_status,id';
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'phone' => 'telefone',
            'email' => 'e-mail',
            'city' => 'cidade',
            'urgency_id' => 'urgência',
            'service_type_id' => 'tipo de serviço',
            'source' => 'origem',
            'status_id' => 'status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'email' => 'O :attribute deve ser um endereço de e-mail válido.',
            'exists' => 'O :attribute selecionado é inválido.',
            'max' => 'O campo :attribute não pode ter mais que :max caracteres.',
        ];
    }
}
