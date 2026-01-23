@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<section class="section" style="background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%); padding-bottom: var(--spacing-8);">
    <div class="container">
        <h1>Perguntas Frequentes</h1>
        <p class="text-light" style="font-size: var(--font-size-xl); max-width: 600px;">
            Encontre respostas para as duvidas mais comuns sobre nossos servicos.
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
            {{-- FAQ Estatico se nao houver categorias no banco --}}
            <div class="mb-8">
                <h2>Sobre os Servicos</h2>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        Quais tipos de cuidado voces oferecem?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Oferecemos cuidado para idosos, pessoas com deficiencia (PCD), pessoas com TEA e acompanhamento pos-operatorio. Os servicos podem ser contratados por hora (horista), por turno (diario) ou em escala mensal.
                    </div>
                </details>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        Qual o prazo para iniciar o atendimento?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Dependendo da urgencia e disponibilidade, podemos iniciar o atendimento no mesmo dia. Para casos nao urgentes, o prazo medio e de 24 a 48 horas.
                    </div>
                </details>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        Como funciona a selecao dos cuidadores?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Todos os cuidadores passam por um processo de verificacao que inclui: validacao de documentos, analise de experiencia anterior, verificacao de referencias e avaliacao de perfil. Apenas profissionais aprovados podem atender pela plataforma.
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
                        O pagamento e sempre adiantado, com antecedencia minima de 24 horas antes do inicio do servico. Aceitamos PIX, boleto e cartao de credito.
                    </div>
                </details>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        Qual a politica de cancelamento?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Cancelamento gratuito se feito com mais de 24 horas de antecedencia. Entre 6 e 24 horas, reembolso de 50%. Com menos de 6 horas de antecedencia, nao ha reembolso. Veja a <a href="{{ route('legal.cancellation') }}">politica completa</a>.
                    </div>
                </details>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        O que acontece se o cuidador nao comparecer?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Em caso de cancelamento pelo cuidador, voce recebe reembolso total e buscamos um substituto imediatamente. Temos politica de substituicao garantida para nao deixar voce sem suporte.
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
                        Preencha o <a href="{{ route('caregivers') }}">formulario de cadastro</a>. Nossa equipe analisara seu perfil e entrara em contato para os proximos passos, que incluem verificacao de documentos e assinatura de contrato.
                    </div>
                </details>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        Quanto eu recebo por atendimento?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Cuidadores recebem entre 70% e 75% do valor do servico, dependendo do tipo de contratacao. Alem disso, ha bonus de ate 2% por avaliacao alta e ate 3% por tempo de casa.
                    </div>
                </details>

                <details class="card mb-4" style="cursor: pointer;">
                    <summary style="font-weight: 600; padding: var(--spacing-2) 0;">
                        Quando recebo meu pagamento?
                    </summary>
                    <div style="padding-top: var(--spacing-4); color: var(--color-text-light);">
                        Os repasses sao feitos semanalmente, todas as sextas-feiras. O valor minimo para repasse e de R$ 50,00 e a liberacao ocorre 3 dias apos a conclusao do servico.
                    </div>
                </details>
            </div>
        @endif

        {{-- CTA --}}
        <div class="highlight-box text-center" style="margin-top: var(--spacing-12);">
            <h3>Nao encontrou o que procurava?</h3>
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
