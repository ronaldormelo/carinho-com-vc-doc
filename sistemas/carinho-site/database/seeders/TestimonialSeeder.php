<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    /**
     * Seed testimonials.
     */
    public function run(): void
    {
        $testimonials = [
            [
                'name' => 'Maria Silva',
                'role' => 'Filha de paciente',
                'content' => 'A Carinho com Você foi uma bênção para nossa família. Encontramos uma cuidadora maravilhosa em menos de 24 horas. O processo foi simples e a equipe super atençiosa.',
                'rating' => 5,
                'featured' => true,
            ],
            [
                'name' => 'João Santos',
                'role' => 'Filho de paciente',
                'content' => 'Após tentar várias agências, finalmente encontrei um serviço que realmente funciona. A cuidadora é pontual, carinhosa e minha mãe adora ela. Recomendo!',
                'rating' => 5,
                'featured' => true,
            ],
            [
                'name' => 'Ana Costa',
                'role' => 'Neta de paciente',
                'content' => 'Precisávamos de um cuidador urgente para minha avó e a Carinho nos atendeu no mesmo dia. O atendimento pelo WhatsApp foi rápido e eficiente.',
                'rating' => 5,
                'featured' => true,
            ],
            [
                'name' => 'Roberto Lima',
                'role' => 'Cuidador parceiro',
                'content' => 'Como cuidador, me sinto valorizado pela Carinho. Os pagamentos são pontuais, tenho suporte quando preciso e sempre tenho oportunidades de trabalho.',
                'rating' => 5,
                'featured' => false,
            ],
            [
                'name' => 'Patrícia Oliveira',
                'role' => 'Esposa de paciente',
                'content' => 'Quando meu marido precisou de cuidados após a cirurgia, a Carinho foi essencial. O processo foi todo digital e muito prático. O cuidador era excelente.',
                'rating' => 4,
                'featured' => true,
            ],
            [
                'name' => 'Carlos Ferreira',
                'role' => 'Filho de paciente',
                'content' => 'Ótimo serviço! Minha mãe recebe cuidados diários e a escala nunca falha. Quando houve uma substituição, foi tudo bem comunicado e sem problemas.',
                'rating' => 5,
                'featured' => true,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::firstOrCreate(
                ['name' => $testimonial['name']],
                array_merge($testimonial, ['active' => true])
            );
        }
    }
}
