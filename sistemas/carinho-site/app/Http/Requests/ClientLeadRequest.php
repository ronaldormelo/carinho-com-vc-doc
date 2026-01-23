<?php

namespace App\Http\Requests;

use App\Models\Domain\DomainServiceType;
use App\Models\Domain\DomainUrgencyLevel;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacao do formulario de lead cliente.
 */
class ClientLeadRequest extends FormRequest
{
    /**
     * Autoriza a requisicao.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validacao.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['required', 'string', 'max:128'],
            'neighborhood' => ['nullable', 'string', 'max:128'],
            'urgency_id' => ['required', 'integer', 'in:' . implode(',', [
                DomainUrgencyLevel::HOJE,
                DomainUrgencyLevel::SEMANA,
                DomainUrgencyLevel::SEM_DATA,
            ])],
            'service_type_id' => ['required', 'integer', 'in:' . implode(',', [
                DomainServiceType::HORISTA,
                DomainServiceType::DIARIO,
                DomainServiceType::MENSAL,
            ])],
            'patient_name' => ['nullable', 'string', 'max:255'],
            'patient_condition' => ['nullable', 'string', 'max:500'],
            'preferred_schedule' => ['nullable', 'string', 'max:500'],
            'message' => ['nullable', 'string', 'max:1000'],
            'consent' => ['required', 'accepted'],
            'recaptcha_token' => ['nullable', 'string'],
        ];
    }

    /**
     * Mensagens de erro customizadas.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Por favor, informe seu nome.',
            'name.min' => 'O nome deve ter pelo menos 3 caracteres.',
            'phone.required' => 'Por favor, informe seu telefone.',
            'phone.min' => 'O telefone deve ter pelo menos 10 digitos.',
            'email.email' => 'Por favor, informe um e-mail valido.',
            'city.required' => 'Por favor, informe sua cidade.',
            'urgency_id.required' => 'Por favor, selecione a urgencia.',
            'urgency_id.in' => 'Urgencia invalida.',
            'service_type_id.required' => 'Por favor, selecione o tipo de servico.',
            'service_type_id.in' => 'Tipo de servico invalido.',
            'consent.required' => 'Voce precisa concordar com os termos para continuar.',
            'consent.accepted' => 'Voce precisa concordar com os termos para continuar.',
        ];
    }

    /**
     * Prepara os dados para validacao.
     */
    protected function prepareForValidation(): void
    {
        // Normaliza telefone (remove caracteres nao numericos)
        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/\D+/', '', $this->phone),
            ]);
        }
    }
}
