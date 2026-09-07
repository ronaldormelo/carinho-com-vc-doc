<?php

namespace App\Http\Requests;

use App\Models\Domain\DomainServiceType;
use App\Models\Domain\DomainUrgencyLevel;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Ingestão pública de lead (site, WhatsApp, hub).
 * Campos operacionais do CRM autenticado continuam em LeadRequest.
 */
class PublicLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'source' => $this->input('source') ?: 'site',
            'city' => $this->input('city') ?: 'nao_informada',
            'urgency_id' => $this->input('urgency_id') ?: DomainUrgencyLevel::SEM_DATA,
            'service_type_id' => $this->input('service_type_id') ?: DomainServiceType::HORISTA,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'email' => 'nullable|email|max:255',
            'city' => 'required|string|max:128',
            'urgency_id' => 'required|integer',
            'service_type_id' => 'required|integer',
            'source' => 'required|string|max:128',
            'utm_id' => 'nullable|integer',
        ];
    }
}
