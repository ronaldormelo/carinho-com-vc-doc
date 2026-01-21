<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
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
            'assigned_to' => 'nullable|exists:users,id',
            'due_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['status_id'] = 'sometimes|exists:domain_task_status,id';
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
            'assigned_to' => 'responsável',
            'due_at' => 'data de vencimento',
            'status_id' => 'status',
            'notes' => 'observações',
        ];
    }
}
