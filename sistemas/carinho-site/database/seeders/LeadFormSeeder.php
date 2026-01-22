<?php

namespace Database\Seeders;

use App\Models\Domain\DomainFormTarget;
use App\Models\LeadForm;
use Illuminate\Database\Seeder;

class LeadFormSeeder extends Seeder
{
    /**
     * Seed lead forms.
     */
    public function run(): void
    {
        // Formulario para clientes
        LeadForm::firstOrCreate(
            ['target_type_id' => DomainFormTarget::CLIENTE],
            [
                'name' => 'Formulario de Cliente',
                'fields_json' => [
                    ['name' => 'name', 'type' => 'text', 'required' => true],
                    ['name' => 'phone', 'type' => 'tel', 'required' => true],
                    ['name' => 'email', 'type' => 'email', 'required' => false],
                    ['name' => 'city', 'type' => 'text', 'required' => true],
                    ['name' => 'urgency_id', 'type' => 'select', 'required' => true],
                    ['name' => 'service_type_id', 'type' => 'select', 'required' => true],
                    ['name' => 'patient_condition', 'type' => 'textarea', 'required' => false],
                    ['name' => 'message', 'type' => 'textarea', 'required' => false],
                ],
                'active' => true,
            ]
        );

        // Formulario para cuidadores
        LeadForm::firstOrCreate(
            ['target_type_id' => DomainFormTarget::CUIDADOR],
            [
                'name' => 'Formulario de Cuidador',
                'fields_json' => [
                    ['name' => 'name', 'type' => 'text', 'required' => true],
                    ['name' => 'phone', 'type' => 'tel', 'required' => true],
                    ['name' => 'email', 'type' => 'email', 'required' => true],
                    ['name' => 'city', 'type' => 'text', 'required' => true],
                    ['name' => 'experience_years', 'type' => 'number', 'required' => true],
                    ['name' => 'has_course', 'type' => 'checkbox', 'required' => false],
                    ['name' => 'specialties', 'type' => 'checkbox_group', 'required' => false],
                    ['name' => 'availability', 'type' => 'textarea', 'required' => false],
                ],
                'active' => true,
            ]
        );
    }
}
