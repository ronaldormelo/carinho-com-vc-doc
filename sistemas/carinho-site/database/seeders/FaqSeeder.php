<?php

namespace Database\Seeders;

use App\Models\FaqCategory;
use App\Models\FaqItem;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Seed FAQ categories and items.
     */
    public function run(): void
    {
        // Categoria: Sobre os Servicos
        $servicos = FaqCategory::firstOrCreate(
            ['slug' => 'sobre-os-servicos'],
            ['name' => 'Sobre os Servicos', 'sort_order' => 1, 'active' => true]
        );

        $this->createFaqItems($servicos->id, [
            [
                'question' => 'Quais tipos de cuidado voces oferecem?',
                'answer' => 'Oferecemos cuidado para idosos, pessoas com deficiencia (PCD), pessoas com TEA e acompanhamento pos-operatorio. Os servicos podem ser contratados por hora (horista), por turno (diario) ou em escala mensal.',
            ],
            [
                'question' => 'Qual o prazo para iniciar o atendimento?',
                'answer' => 'Dependendo da urgencia e disponibilidade, podemos iniciar o atendimento no mesmo dia. Para casos nao urgentes, o prazo medio e de 24 a 48 horas.',
            ],
            [
                'question' => 'Como funciona a selecao dos cuidadores?',
                'answer' => 'Todos os cuidadores passam por um processo de verificacao que inclui: validacao de documentos, analise de experiencia anterior, verificacao de referencias e avaliacao de perfil. Apenas profissionais aprovados podem atender pela plataforma.',
            ],
        ]);

        // Categoria: Pagamento e Cancelamento
        $pagamento = FaqCategory::firstOrCreate(
            ['slug' => 'pagamento-e-cancelamento'],
            ['name' => 'Pagamento e Cancelamento', 'sort_order' => 2, 'active' => true]
        );

        $this->createFaqItems($pagamento->id, [
            [
                'question' => 'Como funciona o pagamento?',
                'answer' => 'O pagamento e sempre adiantado, com antecedencia minima de 24 horas antes do inicio do servico. Aceitamos PIX, boleto e cartao de credito.',
            ],
            [
                'question' => 'Qual a politica de cancelamento?',
                'answer' => "Cancelamento gratuito se feito com mais de 24 horas de antecedencia. Entre 6 e 24 horas, reembolso de 50%. Com menos de 6 horas de antecedencia, nao ha reembolso.",
            ],
            [
                'question' => 'O que acontece se o cuidador nao comparecer?',
                'answer' => 'Em caso de cancelamento pelo cuidador, voce recebe reembolso total e buscamos um substituto imediatamente. Temos politica de substituicao garantida para nao deixar voce sem suporte.',
            ],
        ]);

        // Categoria: Para Cuidadores
        $cuidadores = FaqCategory::firstOrCreate(
            ['slug' => 'para-cuidadores'],
            ['name' => 'Para Cuidadores', 'sort_order' => 3, 'active' => true]
        );

        $this->createFaqItems($cuidadores->id, [
            [
                'question' => 'Como me torno um cuidador parceiro?',
                'answer' => 'Preencha o formulario de cadastro no site. Nossa equipe analisara seu perfil e entrara em contato para os proximos passos, que incluem verificacao de documentos e assinatura de contrato.',
            ],
            [
                'question' => 'Quanto eu recebo por atendimento?',
                'answer' => 'Cuidadores recebem entre 70% e 75% do valor do servico, dependendo do tipo de contratacao. Alem disso, ha bonus de ate 2% por avaliacao alta e ate 3% por tempo de casa.',
            ],
            [
                'question' => 'Quando recebo meu pagamento?',
                'answer' => 'Os repasses sao feitos semanalmente, todas as sextas-feiras. O valor minimo para repasse e de R$ 50,00 e a liberacao ocorre 3 dias apos a conclusao do servico.',
            ],
        ]);
    }

    /**
     * Create FAQ items for a category.
     */
    private function createFaqItems(int $categoryId, array $items): void
    {
        foreach ($items as $index => $item) {
            FaqItem::firstOrCreate(
                [
                    'category_id' => $categoryId,
                    'question' => $item['question'],
                ],
                [
                    'answer' => $item['answer'],
                    'sort_order' => $index + 1,
                    'active' => true,
                ]
            );
        }
    }
}
