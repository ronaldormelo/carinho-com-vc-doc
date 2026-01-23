<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacao do formulario de lead cuidador.
 */
class CaregiverLeadRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'city' => ['required', 'string', 'max:128'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:50'],
            'has_course' => ['nullable', 'boolean'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['string', 'in:idoso,pcd,tea,pos_operatorio,crianca,acompanhamento'],
            'availability' => ['nullable', 'string', 'max:500'],
            'neighborhoods' => ['nullable', 'array'],
            'neighborhoods.*' => ['string', 'max:128'],
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
            'email.required' => 'Por favor, informe seu e-mail.',
            'email.email' => 'Por favor, informe um e-mail valido.',
            'city.required' => 'Por favor, informe sua cidade.',
            'experience_years.required' => 'Por favor, informe seus anos de experiencia.',
            'experience_years.integer' => 'Anos de experiencia deve ser um numero.',
            'experience_years.min' => 'Anos de experiencia nao pode ser negativo.',
            'specialties.array' => 'Especialidades deve ser uma lista.',
            'specialties.*.in' => 'Especialidade invalida selecionada.',
            'consent.required' => 'Voce precisa concordar com os termos para continuar.',
            'consent.accepted' => 'Voce precisa concordar com os termos para continuar.',
        ];
    }

    /**
     * Prepara os dados para validacao.
     */
    protected function prepareForValidation(): void
    {
        // Normaliza telefone
        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/\D+/', '', $this->phone),
            ]);
        }

        // Converte has_course para boolean
        if ($this->has('has_course')) {
            $this->merge([
                'has_course' => filter_var($this->has_course, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
