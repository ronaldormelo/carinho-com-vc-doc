@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<section class="section" style="background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%); padding-bottom: var(--spacing-8);">
    <div class="container">
        <h1>Nossos Servicos</h1>
        <p class="text-light" style="font-size: var(--font-size-xl); max-width: 600px;">
            Oferecemos diferentes modalidades de cuidado para atender as necessidades da sua familia.
        </p>
    </div>
</section>

{{-- Tipos de Servico --}}
<section class="section">
    <div class="container">
        <div class="grid grid-3">
            @foreach($serviceTypes as $key => $service)
            <div class="card" style="{{ $key === 'diario' ? 'border: 2px solid var(--color-primary);' : '' }}">
                @if($key === 'diario')
                <span style="background: var(--color-primary); color: white; padding: 4px 12px; border-radius: var(--border-radius); font-size: var(--font-size-sm); display: inline-block; margin-bottom: var(--spacing-4);">Mais procurado</span>
                @endif

                <div class="feature-icon" style="margin-bottom: var(--spacing-4);">
                    @if($service['icon'] === 'clock')
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    @elseif($service['icon'] === 'sun')
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                    @else
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    @endif
                </div>

                <h3 class="card-title">{{ $service['label'] }}</h3>
                <p class="card-text">{{ $service['description'] }}</p>

                <ul style="color: var(--color-text-light); padding-left: 20px; margin-bottom: var(--spacing-4);">
                    <li>Minimo de {{ $service['min_hours'] }} horas</li>
                    <li>Cuidadores qualificados</li>
                    <li>Substituicao garantida</li>
                </ul>

                <a href="{{ route('whatsapp.cta') }}" class="btn {{ $key === 'diario' ? 'btn-primary' : 'btn-secondary' }} btn-block" target="_blank" rel="noopener">
                    Solicitar orcamento
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Tipos de Cuidado --}}
<section class="section section-alt">
    <div class="container">
        <h2 class="text-center mb-8">Tipos de Cuidado</h2>

        <div class="grid grid-4">
            <div class="card text-center">
                <h4>Idosos</h4>
                <p class="text-muted">Acompanhamento, higiene, alimentacao e medicacao.</p>
            </div>

            <div class="card text-center">
                <h4>PCD</h4>
                <p class="text-muted">Cuidado especializado para pessoas com deficiencia.</p>
            </div>

            <div class="card text-center">
                <h4>TEA</h4>
                <p class="text-muted">Acompanhamento para pessoas com autismo.</p>
            </div>

            <div class="card text-center">
                <h4>Pos-operatorio</h4>
                <p class="text-muted">Recuperacao apos cirurgias e procedimentos.</p>
            </div>
        </div>
    </div>
</section>

{{-- O que esta incluso --}}
<section class="section">
    <div class="container">
        <h2 class="text-center mb-8">O que esta incluso</h2>

        <div class="grid grid-2" style="gap: var(--spacing-8);">
            <div>
                <h4 class="text-primary">Incluso em todos os servicos</h4>
                <ul style="color: var(--color-text-light); padding-left: 20px;">
                    <li>Cuidador qualificado e verificado</li>
                    <li>Contrato digital</li>
                    <li>Check-in e check-out do cuidador</li>
                    <li>Canal de suporte para duvidas</li>
                    <li>Substituicao em caso de ausencia</li>
                    <li>Feedback pos-servico</li>
                </ul>
            </div>

            <div>
                <h4 class="text-primary">Atividades do cuidador</h4>
                <ul style="color: var(--color-text-light); padding-left: 20px;">
                    <li>Acompanhamento e supervisao</li>
                    <li>Auxilio na higiene pessoal</li>
                    <li>Auxilio na alimentacao</li>
                    <li>Administracao de medicamentos</li>
                    <li>Auxilio na mobilidade</li>
                    <li>Companhia e atividades de lazer</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- Politicas Resumidas --}}
<section class="section section-alt">
    <div class="container">
        <h2 class="text-center mb-8">Informacoes Importantes</h2>

        <div class="grid grid-3">
            <div class="card">
                <h4>Pagamento</h4>
                <p class="text-muted">
                    O pagamento e sempre <strong>adiantado</strong>, com antecedencia de 24 horas antes do servico.
                    Aceitamos PIX, boleto e cartao.
                </p>
                <a href="{{ route('legal.payment') }}">Ver politica completa</a>
            </div>

            <div class="card">
                <h4>Cancelamento</h4>
                <p class="text-muted">
                    Cancelamento gratuito ate 24h antes. Entre 6h e 24h, reembolso de 50%.
                    Menos de 6h, sem reembolso.
                </p>
                <a href="{{ route('legal.cancellation') }}">Ver politica completa</a>
            </div>

            <div class="card">
                <h4>Emergencias</h4>
                <p class="text-muted">
                    Temos canal exclusivo para emergencias com resposta em ate 15 minutos
                    para casos criticos.
                </p>
                <a href="{{ route('legal.emergency') }}">Ver politica completa</a>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="cta-section">
    <div class="container">
        <h2>Pronto para contratar?</h2>
        <p>Fale conosco e receba uma proposta personalizada.</p>
        <div style="display: flex; gap: var(--spacing-4); justify-content: center; flex-wrap: wrap;">
            <a href="{{ route('whatsapp.cta') }}" class="btn btn-secondary btn-lg" target="_blank" rel="noopener">
                Falar pelo WhatsApp
            </a>
            <a href="{{ route('clients') }}" class="btn btn-primary btn-lg" style="background: white; color: var(--color-primary);">
                Preencher formulario
            </a>
        </div>
    </div>
</section>
@endsection
