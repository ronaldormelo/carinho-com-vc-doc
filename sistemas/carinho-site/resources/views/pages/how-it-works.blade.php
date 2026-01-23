@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<section class="section" style="background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%); padding-bottom: var(--spacing-8);">
    <div class="container">
        <h1>Como Funciona</h1>
        <p class="text-light" style="font-size: var(--font-size-xl); max-width: 600px;">
            Veja como e facil contratar um cuidador pela Carinho com Voce.
        </p>
    </div>
</section>

{{-- Passo a Passo --}}
<section class="section">
    <div class="container" style="max-width: 800px;">
        <h2 class="text-center mb-8">Processo Simples em 6 Passos</h2>

        {{-- Step 1 --}}
        <div class="card mb-6" style="display: flex; gap: var(--spacing-6); align-items: flex-start;">
            <div style="width: 48px; height: 48px; min-width: 48px; background: var(--color-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px;">1</div>
            <div>
                <h3 style="margin-top: 0;">Entre em Contato</h3>
                <p class="text-muted">
                    Fale conosco pelo WhatsApp ou preencha o formulario no site. Resposta garantida em ate 5 minutos durante o horario comercial.
                </p>
            </div>
        </div>

        {{-- Step 2 --}}
        <div class="card mb-6" style="display: flex; gap: var(--spacing-6); align-items: flex-start;">
            <div style="width: 48px; height: 48px; min-width: 48px; background: var(--color-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px;">2</div>
            <div>
                <h3 style="margin-top: 0;">Entendemos sua Necessidade</h3>
                <p class="text-muted">
                    Fazemos algumas perguntas para entender o perfil do paciente, tipo de cuidado necessario, horarios e preferencias. Tudo pelo WhatsApp, de forma rapida e pratica.
                </p>
            </div>
        </div>

        {{-- Step 3 --}}
        <div class="card mb-6" style="display: flex; gap: var(--spacing-6); align-items: flex-start;">
            <div style="width: 48px; height: 48px; min-width: 48px; background: var(--color-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px;">3</div>
            <div>
                <h3 style="margin-top: 0;">Receba a Proposta</h3>
                <p class="text-muted">
                    Enviamos uma proposta transparente com valores, tipo de servico e perfil dos cuidadores disponiveis. Sem surpresas, sem letras miudas.
                </p>
            </div>
        </div>

        {{-- Step 4 --}}
        <div class="card mb-6" style="display: flex; gap: var(--spacing-6); align-items: flex-start;">
            <div style="width: 48px; height: 48px; min-width: 48px; background: var(--color-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px;">4</div>
            <div>
                <h3 style="margin-top: 0;">Aprove e Assine o Contrato</h3>
                <p class="text-muted">
                    Aprovando a proposta, enviamos o contrato digital para assinatura. E rapido, seguro e 100% online.
                </p>
            </div>
        </div>

        {{-- Step 5 --}}
        <div class="card mb-6" style="display: flex; gap: var(--spacing-6); align-items: flex-start;">
            <div style="width: 48px; height: 48px; min-width: 48px; background: var(--color-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px;">5</div>
            <div>
                <h3 style="margin-top: 0;">Realize o Pagamento</h3>
                <p class="text-muted">
                    O pagamento e adiantado, feito com antecedencia de 24h antes do servico. Aceitamos PIX, boleto e cartao.
                </p>
            </div>
        </div>

        {{-- Step 6 --}}
        <div class="card mb-6" style="display: flex; gap: var(--spacing-6); align-items: flex-start; border: 2px solid var(--color-success);">
            <div style="width: 48px; height: 48px; min-width: 48px; background: var(--color-success); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px;">6</div>
            <div>
                <h3 style="margin-top: 0; color: var(--color-success);">Cuidador Inicia o Atendimento!</h3>
                <p class="text-muted">
                    No dia e horario combinados, o cuidador chega e faz check-in pelo app. Voce recebe notificacao de inicio e fim do atendimento.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- Diferenciais --}}
<section class="section section-alt">
    <div class="container">
        <h2 class="text-center mb-8">O que nos diferencia</h2>

        <div class="grid grid-3">
            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <h4>Resposta em 5 minutos</h4>
                <p class="text-muted">Nao deixamos voce esperando. Atendimento agil no horario comercial.</p>
            </div>

            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                </div>
                <h4>100% Digital</h4>
                <p class="text-muted">Tudo pelo WhatsApp e online. Sem visitas desnecessarias, sem burocracia.</p>
            </div>

            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h4>Substituicao Garantida</h4>
                <p class="text-muted">Se o cuidador nao puder ir, encontramos outro rapidamente.</p>
            </div>
        </div>
    </div>
</section>

{{-- Durante o Atendimento --}}
<section class="section">
    <div class="container" style="max-width: 800px;">
        <h2 class="text-center mb-8">Durante o Atendimento</h2>

        <div class="grid grid-2" style="gap: var(--spacing-6);">
            <div class="card">
                <h4>Check-in e Check-out</h4>
                <p class="text-muted">
                    O cuidador registra chegada e saida pelo aplicativo. Voce recebe notificacoes em tempo real.
                </p>
            </div>

            <div class="card">
                <h4>Registro de Atividades</h4>
                <p class="text-muted">
                    O cuidador registra as atividades realizadas durante o atendimento, mantendo voce informado.
                </p>
            </div>

            <div class="card">
                <h4>Canal de Suporte</h4>
                <p class="text-muted">
                    Qualquer duvida ou problema durante o atendimento, nosso suporte esta a disposicao.
                </p>
            </div>

            <div class="card">
                <h4>Feedback Pos-Servico</h4>
                <p class="text-muted">
                    Apos cada atendimento, pedimos seu feedback para garantir a qualidade do servico.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="cta-section">
    <div class="container">
        <h2>Pronto para comecar?</h2>
        <p>Fale conosco agora e receba uma proposta em minutos!</p>
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
