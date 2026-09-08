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
            ['name' => 'Sobre os Serviços', 'sort_order' => 1, 'active' => true]
        );

        $this->createFaqItems($servicos->id, [
            [
                'question' => 'Quais tipos de cuidado vocês oferecem?',
                'answer' => 'Oferecemos cuidado para idosos, pessoas com deficiência (PCD), pessoas com TEA e acompanhamento pós-operatório. Os serviços podem ser contratados por hora (horista), por turno (diário) ou em escala mensal.',
            ],
            [
                'question' => 'Qual o prazo para iniciar o atendimento?',
                'answer' => 'Dependendo da urgência e disponibilidade, podemos iniciar o atendimento no mesmo dia. Para casos não urgentes, o prazo médio é de 24 a 48 horas.',
            ],
            [
                'question' => 'Como funciona a seleção dos cuidadores?',
                'answer' => 'Todos os cuidadores passam por um processo de verificação que inclui: validação de documentos, análise de experiência anterior, verificação de referências e avaliação de perfil. Apenas profissionais aprovados podem atender pela plataforma.',
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
                'answer' => 'O pagamento é sempre adiantado, com antecedência mínima de 24 horas antes do início do serviço. Aceitamos PIX, boleto e cartão de crédito.',
            ],
            [
                'question' => 'Qual a política de cancelamento?',
                'answer' => "Cancelamento gratuito se feito com mais de 24 horas de antecedência. Entre 6 e 24 horas, reembolso de 50%. Com menos de 6 horas de antecedência, não há reembolso.",
            ],
            [
                'question' => 'O que acontece se o cuidador não comparecer?',
                'answer' => 'Em caso de cancelamento pelo cuidador, você recebe reembolso total e buscamos um substituto imediatamente. Temos política de substituição garantida para não deixar você sem suporte.',
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
                'answer' => 'Preencha o formulário de cadastro no site. Nossa equipe analisará seu perfil e entrará em contato para os próximos passos, que incluem verificação de documentos e assinatura de contrato.',
            ],
            [
                'question' => 'Quanto eu recebo por atendimento?',
                'answer' => 'Cuidadores recebem entre 70% e 75% do valor do serviço, dependendo do tipo de contratação. Além disso, há bônus de até 2% por avaliação alta e até 3% por tempo de casa.',
            ],
            [
                'question' => 'Quando recebo meu pagamento?',
                'answer' => 'Os repasses são feitos semanalmente, todas as sextas-feiras. O valor mínimo para repasse e de R$ 50,00 e a liberação ocorre 3 dias após a conclusão do serviço.',
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
