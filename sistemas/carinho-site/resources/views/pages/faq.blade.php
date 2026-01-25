@extends('layouts.app')

@push('styles')
{{-- Schema.org FAQ para SEO --}}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "Quais tipos de cuidado vocês oferecem?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Oferecemos cuidado para idosos, pessoas com deficiência (PCD), pessoas com TEA e acompanhamento pós-operatório. Os serviços podem ser contratados por hora (horista), por turno (diário) ou em escala mensal."
            }
        },
        {
            "@type": "Question",
            "name": "Qual o prazo para iniciar o atendimento?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Dependendo da urgência e disponibilidade, podemos iniciar o atendimento no mesmo dia. Para casos não urgentes, o prazo médio é de 24 a 48 horas."
            }
        },
        {
            "@type": "Question",
            "name": "Como funciona o pagamento?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "O pagamento é sempre adiantado, com antecedência mínima de 24 horas antes do início do serviço. Aceitamos PIX, boleto e cartão de crédito."
            }
        },
        {
            "@type": "Question",
            "name": "Qual a política de cancelamento?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Cancelamento gratuito se feito com mais de 24 horas de antecedência. Entre 6 e 24 horas, reembolso de 50%. Com menos de 6 horas de antecedência, não há reembolso."
            }
        },
        {
            "@type": "Question",
            "name": "Quanto o cuidador recebe por atendimento?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Cuidadores recebem entre 70% e 75% do valor do serviço, dependendo do tipo de contratação. Além disso, há bônus de até 2% por avaliação alta e até 3% por tempo de casa."
            }
        }
    ]
}
</script>
@endpush

@section('content')
{{-- Breadcrumb --}}
<nav aria-label="Breadcrumb" style="background: var(--bg-secondary); padding: var(--spacing-3) 0;">
    <div class="container">
        <ol style="list-style: none; padding: 0; margin: 0; display: flex; gap: var(--spacing-2); font-size: var(--font-size-sm); color: var(--color-text-muted);">
            <li><a href="{{ route('home') }}" style="color: var(--color-text-muted);">Início</a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" style="color: var(--color-primary);">Perguntas Frequentes</li>
        </ol>
    </div>
</nav>

{{-- Page Header --}}
<section class="section" style="background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%); padding-bottom: var(--spacing-8);">
    <div class="container">
        <h1>Perguntas Frequentes</h1>
        <p class="text-light" style="font-size: var(--font-size-xl); max-width: 600px;">
            Encontre respostas para as dúvidas mais comuns sobre nossos serviços.
        </p>
    </div>
</section>

{{-- FAQ --}}
<section class="section">
    <div class="container" style="max-width: 800px;">
        @if($categories->count() > 0)
            @foreach($categories as $category)
            <div class="mb-8">
                <h2>{{ $category->name }}</h2>

                @foreach($category->items as $item)
                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        {{ $item->question }}
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        {!! nl2br(e($item->answer)) !!}
                    </div>
                </details>
                @endforeach
            </div>
            @endforeach
        @else
            {{-- FAQ Estático se não houver categorias no banco --}}
            <div class="mb-8">
                <h2>Sobre os Serviços</h2>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        Quais tipos de cuidado vocês oferecem?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Oferecemos cuidado para idosos, pessoas com deficiência (PCD), pessoas com TEA e acompanhamento pós-operatório. Os serviços podem ser contratados por hora (horista), por turno (diário) ou em escala mensal.
                    </div>
                </details>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        Qual o prazo para iniciar o atendimento?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Dependendo da urgência e disponibilidade, podemos iniciar o atendimento no mesmo dia. Para casos não urgentes, o prazo médio é de 24 a 48 horas.
                    </div>
                </details>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        Como funciona a seleção dos cuidadores?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Todos os cuidadores passam por um processo de verificação que inclui: validação de documentos, análise de experiência anterior, verificação de referências e avaliação de perfil. Apenas profissionais aprovados podem atender pela plataforma.
                    </div>
                </details>
            </div>

            <div class="mb-8">
                <h2>Pagamento e Cancelamento</h2>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        Como funciona o pagamento?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        O pagamento é sempre adiantado, com antecedência mínima de 24 horas antes do início do serviço. Aceitamos PIX, boleto e cartão de crédito.
                    </div>
                </details>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        Qual a política de cancelamento?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Cancelamento gratuito se feito com mais de 24 horas de antecedência. Entre 6 e 24 horas, reembolso de 50%. Com menos de 6 horas de antecedência, não há reembolso. Veja a <a href="{{ route('legal.cancellation') }}">política completa</a>.
                    </div>
                </details>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        O que acontece se o cuidador não comparecer?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Em caso de cancelamento pelo cuidador, você recebe reembolso total e buscamos um substituto imediatamente. Temos política de substituição garantida para não deixar você sem suporte.
                    </div>
                </details>
            </div>

            <div class="mb-8">
                <h2>Para Cuidadores</h2>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        Como me torno um cuidador parceiro?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Preencha o <a href="{{ route('caregivers') }}">formulário de cadastro</a>. Nossa equipe analisará seu perfil e entrará em contato para os próximos passos, que incluem verificação de documentos e assinatura de contrato.
                    </div>
                </details>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        Quanto eu recebo por atendimento?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Cuidadores recebem entre 70% e 75% do valor do serviço, dependendo do tipo de contratação. Além disso, há bônus de até 2% por avaliação alta e até 3% por tempo de casa.
                    </div>
                </details>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        Quando recebo meu pagamento?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Os repasses são feitos semanalmente, todas as sextas-feiras. O valor mínimo para repasse é de R$ 50,00 e a liberação ocorre 3 dias após a conclusão do serviço.
                    </div>
                </details>
            </div>
        @endif

        {{-- CTA --}}
        <div class="highlight-box text-center" style="margin-top: var(--spacing-12);">
            <h3>Não encontrou o que procurava?</h3>
            <p class="text-muted">Entre em contato pelo WhatsApp e teremos prazer em ajudar!</p>
            <a href="{{ route('whatsapp.cta') }}" class="btn btn-whatsapp" target="_blank" rel="noopener">
                Falar pelo WhatsApp
            </a>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
details summary {
    list-style: none;
}

details summary::-webkit-details-marker {
    display: none;
}

details summary::after {
    content: '+';
    float: right;
    font-size: 1.5rem;
    color: var(--color-primary);
}

details[open] summary::after {
    content: '-';
}
</style>
@endpush
