@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<section class="section" style="background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%); padding-bottom: var(--spacing-8);">
    <div class="container">
        <h1>Contato</h1>
        <p class="text-light" style="font-size: var(--font-size-xl); max-width: 600px;">
            Estamos aqui para ajudar. Entre em contato pelo canal de sua preferencia.
        </p>
    </div>
</section>

{{-- Canais de Contato --}}
<section class="section">
    <div class="container">
        <div class="grid grid-3">
            {{-- WhatsApp --}}
            <div class="card text-center" style="border: 2px solid #25D366;">
                <div style="width: 64px; height: 64px; background: #25D366; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="white">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </div>
                <h3>WhatsApp</h3>
                <p class="text-muted">Nosso principal canal de atendimento. Resposta em ate 5 minutos!</p>
                <p><strong>{{ config('branding.contact.whatsapp_display') }}</strong></p>
                <a href="{{ route('whatsapp.cta') }}" class="btn btn-whatsapp btn-block" target="_blank" rel="noopener">
                    Falar pelo WhatsApp
                </a>
            </div>

            {{-- E-mail --}}
            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
                <h3>E-mail</h3>
                <p class="text-muted">Para propostas, contratos e comunicacoes formais.</p>
                <p><strong>{{ config('branding.contact.email') }}</strong></p>
                <a href="mailto:{{ config('branding.contact.email') }}" class="btn btn-secondary btn-block">
                    Enviar e-mail
                </a>
            </div>

            {{-- Emergencias --}}
            <div class="card text-center" style="border: 2px solid var(--color-danger);">
                <div style="width: 64px; height: 64px; background: var(--color-danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <h3>Emergencias</h3>
                <p class="text-muted">Para situacoes urgentes durante o atendimento.</p>
                <p><strong>{{ config('branding.contact.email_emergency') }}</strong></p>
                <a href="{{ route('legal.emergency') }}" class="btn btn-secondary btn-block">
                    Ver politica de emergencias
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Horario de Atendimento --}}
<section class="section section-alt">
    <div class="container">
        <div class="grid grid-2" style="align-items: center; gap: var(--spacing-12);">
            <div>
                <h2>Horario de Atendimento</h2>
                <p class="text-light">
                    Nossa equipe esta disponivel para atender voce nos seguintes horarios:
                </p>

                <div class="card">
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding: var(--spacing-2) 0;"><strong>Segunda a Sexta</strong></td>
                            <td style="padding: var(--spacing-2) 0; text-align: right;">08:00 - 20:00</td>
                        </tr>
                        <tr>
                            <td style="padding: var(--spacing-2) 0;"><strong>Sabado</strong></td>
                            <td style="padding: var(--spacing-2) 0; text-align: right;">09:00 - 18:00</td>
                        </tr>
                        <tr>
                            <td style="padding: var(--spacing-2) 0;"><strong>Domingo e Feriados</strong></td>
                            <td style="padding: var(--spacing-2) 0; text-align: right;">Somente emergencias</td>
                        </tr>
                    </table>
                </div>

                <p class="text-muted mt-4">
                    Fora do horario comercial, voce pode enviar mensagem pelo WhatsApp que responderemos
                    assim que possivel no proximo dia util.
                </p>
            </div>

            <div>
                <h2>SLA de Atendimento</h2>
                <div class="card" style="background: var(--bg-secondary);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-4);">
                        <span>Primeira resposta</span>
                        <strong style="color: var(--color-primary);">5 minutos</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-4);">
                        <span>Resolucao simples</span>
                        <strong style="color: var(--color-primary);">30 minutos</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-4);">
                        <span>Emergencia critica</span>
                        <strong style="color: var(--color-danger);">15 minutos</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Emergencia alta</span>
                        <strong style="color: var(--color-warning);">30 minutos</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Links Rapidos --}}
<section class="section">
    <div class="container">
        <h2 class="text-center mb-8">Links Rapidos</h2>

        <div class="grid grid-4">
            <a href="{{ route('clients') }}" class="card text-center" style="text-decoration: none;">
                <h4 style="color: var(--color-primary);">Preciso de um Cuidador</h4>
                <p class="text-muted">Solicitar orcamento</p>
            </a>

            <a href="{{ route('caregivers') }}" class="card text-center" style="text-decoration: none;">
                <h4 style="color: var(--color-primary);">Quero ser Cuidador</h4>
                <p class="text-muted">Fazer cadastro</p>
            </a>

            <a href="{{ route('faq') }}" class="card text-center" style="text-decoration: none;">
                <h4 style="color: var(--color-primary);">Perguntas Frequentes</h4>
                <p class="text-muted">Tire suas duvidas</p>
            </a>

            <a href="{{ route('legal.privacy') }}" class="card text-center" style="text-decoration: none;">
                <h4 style="color: var(--color-primary);">Privacidade e LGPD</h4>
                <p class="text-muted">Seus direitos</p>
            </a>
        </div>
    </div>
</section>
@endsection
