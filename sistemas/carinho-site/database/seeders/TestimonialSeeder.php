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
                'content' => 'A Carinho com Voce foi uma bencao para nossa familia. Encontramos uma cuidadora maravilhosa em menos de 24 horas. O processo foi simples e a equipe super atenciosa.',
                'rating' => 5,
                'featured' => true,
            ],
            [
                'name' => 'Joao Santos',
                'role' => 'Filho de paciente',
                'content' => 'Apos tentar varias agencias, finalmente encontrei um servico que realmente funciona. A cuidadora e pontual, carinhosa e minha mae adora ela. Recomendo!',
                'rating' => 5,
                'featured' => true,
            ],
            [
                'name' => 'Ana Costa',
                'role' => 'Neta de paciente',
                'content' => 'Precisavamos de um cuidador urgente para minha avo e a Carinho nos atendeu no mesmo dia. O atendimento pelo WhatsApp foi rapido e eficiente.',
                'rating' => 5,
                'featured' => true,
            ],
            [
                'name' => 'Roberto Lima',
                'role' => 'Cuidador parceiro',
                'content' => 'Como cuidador, me sinto valorizado pela Carinho. Os pagamentos sao pontuais, tenho suporte quando preciso e sempre tenho oportunidades de trabalho.',
                'rating' => 5,
                'featured' => false,
            ],
            [
                'name' => 'Patricia Oliveira',
                'role' => 'Esposa de paciente',
                'content' => 'Quando meu marido precisou de cuidados apos a cirurgia, a Carinho foi essencial. O processo foi todo digital e muito pratico. O cuidador era excelente.',
                'rating' => 4,
                'featured' => true,
            ],
            [
                'name' => 'Carlos Ferreira',
                'role' => 'Filho de paciente',
                'content' => 'Otimo servico! Minha mae recebe cuidados diarios e a escala nunca falha. Quando houve uma substituicao, foi tudo bem comunicado e sem problemas.',
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
