<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
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
            'primary_contact' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'address' => 'nullable|string|max:255',
            'city' => 'required|string|max:128',
            'preferences_json' => 'nullable|array',
            'preferences_json.contact_preference' => 'nullable|string|in:whatsapp,phone,email',
            'preferences_json.best_time' => 'nullable|string',
            'preferences_json.special_requests' => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'lead_id' => 'lead',
            'primary_contact' => 'contato principal',
            'phone' => 'telefone',
            'address' => 'endereço',
            'city' => 'cidade',
            'preferences_json' => 'preferências',
        ];
    }
}
