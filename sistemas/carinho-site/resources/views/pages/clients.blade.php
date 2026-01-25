@extends('layouts.app')

@section('content')
{{-- Breadcrumb --}}
<nav aria-label="Breadcrumb" style="background: var(--bg-secondary); padding: var(--spacing-3) 0;">
    <div class="container">
        <ol style="list-style: none; padding: 0; margin: 0; display: flex; gap: var(--spacing-2); font-size: var(--font-size-sm); color: var(--color-text-muted);">
            <li><a href="{{ route('home') }}" style="color: var(--color-text-muted);">Início</a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" style="color: var(--color-primary);">Para Clientes</li>
        </ol>
    </div>
</nav>

{{-- Page Header --}}
<section class="section" style="background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%); padding-bottom: var(--spacing-8);">
    <div class="container">
        <h1>Preciso de um Cuidador</h1>
        <p class="text-light" style="font-size: var(--font-size-xl); max-width: 600px;">
            Preencha o formulário abaixo e entraremos em contato em minutos!
        </p>
    </div>
</section>

{{-- Formulário --}}
<section class="section">
    <div class="container">
        <div class="grid grid-2" style="gap: var(--spacing-12);">
            {{-- Form --}}
            <div>
                <h2>Solicite um orçamento</h2>
                <p class="text-muted mb-8">Resposta em até 5 minutos no horário comercial.</p>

                <form id="clientLeadForm" method="POST" action="{{ route('lead.client.submit') }}" aria-label="Formulário de solicitação de cuidador">
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="name">Nome completo *</label>
                        <input type="text" id="name" name="name" class="form-input" required placeholder="Seu nome" autocomplete="name">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">WhatsApp *</label>
                        <input type="tel" id="phone" name="phone" class="form-input" required placeholder="(11) 99999-9999" autocomplete="tel">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">E-mail</label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="seu@email.com" autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="city">Cidade *</label>
                        <input type="text" id="city" name="city" class="form-input" required placeholder="São Paulo" autocomplete="address-level2">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="urgency_id">Urgência *</label>
                        <select id="urgency_id" name="urgency_id" class="form-select" required aria-describedby="urgency_help">
                            <option value="">Selecione...</option>
                            @foreach($urgencyLevels as $key => $urgency)
                            <option value="{{ $loop->iteration }}">{{ $urgency['label'] }}</option>
                            @endforeach
                        </select>
                        <span id="urgency_help" class="form-hint">Quando você precisa do cuidador?</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="service_type_id">Tipo de serviço *</label>
                        <select id="service_type_id" name="service_type_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            @foreach($serviceTypes as $key => $service)
                            <option value="{{ $loop->iteration }}">{{ $service['label'] }} - {{ $service['description'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="patient_condition">Sobre o paciente</label>
                        <textarea id="patient_condition" name="patient_condition" class="form-textarea" placeholder="Conte-nos sobre a pessoa que precisa de cuidado (idade, condição, necessidades especiais...)"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="message">Observações adicionais</label>
                        <textarea id="message" name="message" class="form-textarea" placeholder="Horários preferidos, preferências de cuidador, etc."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="consent" required aria-describedby="consent_text">
                            <span id="consent_text">Li e concordo com a <a href="{{ route('legal.privacy') }}" target="_blank">Política de Privacidade</a> e os <a href="{{ route('legal.terms') }}" target="_blank">Termos de Uso</a>. *</span>
                        </label>
                    </div>

                    <input type="hidden" name="recaptcha_token" id="recaptcha_token">

                    <button type="submit" class="btn btn-primary btn-lg btn-block" id="submitBtn">
                        Enviar solicitação
                    </button>

                    <div id="formMessage" class="mt-4" style="display: none;" role="alert" aria-live="polite"></div>
                </form>
            </div>

            {{-- Info --}}
            <div>
                <div class="card" style="background: var(--bg-secondary); margin-bottom: var(--spacing-6);">
                    <h4>Prefere falar diretamente?</h4>
                    <p class="text-muted">Atendimento pelo WhatsApp das 8h às 20h.</p>
                    <a href="{{ route('whatsapp.cta') }}" class="btn btn-whatsapp btn-block" target="_blank" rel="noopener" aria-label="Falar pelo WhatsApp">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;" aria-hidden="true">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Falar pelo WhatsApp
                    </a>
                </div>

                <div class="card">
                    <h4>Como funciona</h4>
                    <ol style="color: var(--color-text-light); padding-left: 20px;">
                        <li style="margin-bottom: var(--spacing-3);">Você envia a solicitação</li>
                        <li style="margin-bottom: var(--spacing-3);">Entramos em contato em até 5 minutos</li>
                        <li style="margin-bottom: var(--spacing-3);">Entendemos sua necessidade</li>
                        <li style="margin-bottom: var(--spacing-3);">Enviamos proposta e opções de cuidadores</li>
                        <li style="margin-bottom: var(--spacing-3);">Você aprova e assinamos contrato digital</li>
                        <li>Cuidador inicia o atendimento!</li>
                    </ol>
                </div>

                <div class="highlight-box mt-4">
                    <p style="margin: 0;"><strong>Atenção:</strong> O pagamento é sempre adiantado, com antecedência de 24h antes do serviço. <a href="{{ route('legal.payment') }}">Saiba mais</a></p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.getElementById('clientLeadForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const form = e.target;
    const submitBtn = document.getElementById('submitBtn');
    const messageDiv = document.getElementById('formMessage');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Enviando...';
    submitBtn.setAttribute('aria-busy', 'true');

    try {
        // Get reCAPTCHA token if enabled
        @if(config('integrations.recaptcha.enabled') && config('integrations.recaptcha.site_key'))
        const token = await grecaptcha.execute('{{ config('integrations.recaptcha.site_key') }}', {action: 'submit_lead'});
        document.getElementById('recaptcha_token').value = token;
        @endif

        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            messageDiv.innerHTML = '<div class="card" style="background: #d4edda; border-color: #c3e6cb; color: #155724;" role="status">' + data.message + '</div>';
            messageDiv.style.display = 'block';
            form.reset();

            // Redirect to WhatsApp if URL provided
            if (data.whatsapp_url) {
                setTimeout(() => {
                    window.open(data.whatsapp_url, '_blank');
                }, 2000);
            }
        } else {
            messageDiv.innerHTML = '<div class="card" style="background: #f8d7da; border-color: #f5c6cb; color: #721c24;" role="alert">' + (data.message || 'Erro ao enviar. Tente novamente.') + '</div>';
            messageDiv.style.display = 'block';
        }
    } catch (error) {
        messageDiv.innerHTML = '<div class="card" style="background: #f8d7da; border-color: #f5c6cb; color: #721c24;" role="alert">Erro ao enviar. Por favor, tente novamente ou entre em contato pelo WhatsApp.</div>';
        messageDiv.style.display = 'block';
    }

    submitBtn.disabled = false;
    submitBtn.textContent = 'Enviar solicitação';
    submitBtn.setAttribute('aria-busy', 'false');
});
</script>
@endpush
