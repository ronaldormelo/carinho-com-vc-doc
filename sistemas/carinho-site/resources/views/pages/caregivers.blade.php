@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<section class="section" style="background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%); padding-bottom: var(--spacing-8);">
    <div class="container">
        <h1>Seja um Cuidador Parceiro</h1>
        <p class="text-light" style="font-size: var(--font-size-xl); max-width: 600px;">
            Junte-se a nossa equipe e tenha mais oportunidades de trabalho com recorrencia e suporte profissional.
        </p>
    </div>
</section>

{{-- Beneficios --}}
<section class="section">
    <div class="container">
        <h2 class="text-center mb-8">Por que ser parceiro?</h2>

        <div class="grid grid-4">
            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <h4>Repasse Justo</h4>
                <p class="text-muted">Receba de {{ $commissions['horista'] }}% a {{ $commissions['mensal'] }}% do valor do servico.</p>
            </div>

            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <h4>Mais Oportunidades</h4>
                <p class="text-muted">Acesso a demandas recorrentes e novos clientes constantemente.</p>
            </div>

            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h4>Pagamento Garantido</h4>
                <p class="text-muted">Repasse semanal as sextas-feiras, sem atrasos.</p>
            </div>

            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <h4>Suporte Dedicado</h4>
                <p class="text-muted">Canal exclusivo para duvidas e orientacoes.</p>
            </div>
        </div>
    </div>
</section>

{{-- Comissoes --}}
<section class="section section-alt">
    <div class="container">
        <h2 class="text-center mb-8">Quanto voce recebe</h2>

        <div class="grid grid-3">
            <div class="card text-center">
                <h3 style="color: var(--color-primary); font-size: var(--font-size-4xl); margin-bottom: var(--spacing-2);">{{ $commissions['horista'] }}%</h3>
                <h4>Horista</h4>
                <p class="text-muted">Atendimentos por hora</p>
            </div>

            <div class="card text-center" style="border: 2px solid var(--color-primary);">
                <h3 style="color: var(--color-primary); font-size: var(--font-size-4xl); margin-bottom: var(--spacing-2);">{{ $commissions['diario'] }}%</h3>
                <h4>Diario</h4>
                <p class="text-muted">Turnos diurnos ou noturnos</p>
            </div>

            <div class="card text-center">
                <h3 style="color: var(--color-primary); font-size: var(--font-size-4xl); margin-bottom: var(--spacing-2);">{{ $commissions['mensal'] }}%</h3>
                <h4>Mensal</h4>
                <p class="text-muted">Escala fixa mensal</p>
            </div>
        </div>

        <div class="highlight-box mt-8" style="max-width: 600px; margin-left: auto; margin-right: auto; text-align: center;">
            <p style="margin: 0;">
                <strong>Bonus adicional:</strong> Ate +{{ $commissions['bonus']['rating'] }}% por avaliacao alta e
                +{{ $commissions['bonus']['tenure'] }}% por tempo de casa!
            </p>
        </div>
    </div>
</section>

{{-- Formulario de Cadastro --}}
<section class="section">
    <div class="container">
        <div class="grid grid-2" style="gap: var(--spacing-12);">
            {{-- Form --}}
            <div>
                <h2>Cadastre-se agora</h2>
                <p class="text-muted mb-8">Preencha seus dados e entraremos em contato.</p>

                <form id="caregiverLeadForm" method="POST" action="{{ route('lead.caregiver.submit') }}">
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="name">Nome completo *</label>
                        <input type="text" id="name" name="name" class="form-input" required placeholder="Seu nome">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">WhatsApp *</label>
                        <input type="tel" id="phone" name="phone" class="form-input" required placeholder="(11) 99999-9999">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">E-mail *</label>
                        <input type="email" id="email" name="email" class="form-input" required placeholder="seu@email.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="city">Cidade *</label>
                        <input type="text" id="city" name="city" class="form-input" required placeholder="Sao Paulo">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="experience_years">Anos de experiencia *</label>
                        <input type="number" id="experience_years" name="experience_years" class="form-input" required min="0" max="50" placeholder="Ex: 3">
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="has_course" value="1">
                            <span>Tenho curso de cuidador de idosos</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Especialidades</label>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--spacing-2);">
                            <label class="form-checkbox">
                                <input type="checkbox" name="specialties[]" value="idoso">
                                <span>Idosos</span>
                            </label>
                            <label class="form-checkbox">
                                <input type="checkbox" name="specialties[]" value="pcd">
                                <span>PCD</span>
                            </label>
                            <label class="form-checkbox">
                                <input type="checkbox" name="specialties[]" value="tea">
                                <span>TEA</span>
                            </label>
                            <label class="form-checkbox">
                                <input type="checkbox" name="specialties[]" value="pos_operatorio">
                                <span>Pos-operatorio</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="availability">Disponibilidade</label>
                        <textarea id="availability" name="availability" class="form-textarea" placeholder="Dias e horarios disponiveis para trabalhar"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="consent" required>
                            <span>Li e concordo com a <a href="{{ route('legal.privacy') }}" target="_blank">Politica de Privacidade</a> e os <a href="{{ route('legal.caregiver-terms') }}" target="_blank">Termos para Cuidadores</a>. *</span>
                        </label>
                    </div>

                    <input type="hidden" name="recaptcha_token" id="recaptcha_token_caregiver">

                    <button type="submit" class="btn btn-primary btn-lg btn-block" id="submitBtnCaregiver">
                        Enviar cadastro
                    </button>

                    <div id="formMessageCaregiver" class="mt-4" style="display: none;"></div>
                </form>
            </div>

            {{-- Info --}}
            <div>
                <div class="card" style="background: var(--bg-secondary); margin-bottom: var(--spacing-6);">
                    <h4>Requisitos</h4>
                    <ul style="color: var(--color-text-light); padding-left: 20px;">
                        <li>Minimo 1 ano de experiencia comprovada</li>
                        <li>Curso de cuidador de idosos (desejavel)</li>
                        <li>Postura profissional e pontualidade</li>
                        <li>Disponibilidade informada e atualizada</li>
                        <li>Documentos em dia</li>
                    </ul>
                </div>

                <div class="card">
                    <h4>Proximos passos</h4>
                    <ol style="color: var(--color-text-light); padding-left: 20px;">
                        <li style="margin-bottom: var(--spacing-3);">Voce envia o cadastro</li>
                        <li style="margin-bottom: var(--spacing-3);">Analisamos seu perfil</li>
                        <li style="margin-bottom: var(--spacing-3);">Entramos em contato para conversar</li>
                        <li style="margin-bottom: var(--spacing-3);">Solicitamos documentos para verificacao</li>
                        <li style="margin-bottom: var(--spacing-3);">Assinamos contrato digital</li>
                        <li>Voce comeca a receber oportunidades!</li>
                    </ol>
                </div>

                <div class="card mt-4" style="background: var(--bg-secondary);">
                    <h4>Politica de Repasse</h4>
                    <p class="text-muted">
                        <strong>Frequencia:</strong> Semanal (sextas-feiras)<br>
                        <strong>Valor minimo:</strong> R$ {{ number_format($payoutPolicy['min_value'], 2, ',', '.') }}<br>
                        <strong>Liberacao:</strong> {{ $payoutPolicy['release_days'] }} dias apos conclusao do servico
                    </p>
                    <a href="{{ route('legal.payment') }}">Ver politica completa</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.getElementById('caregiverLeadForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const form = e.target;
    const submitBtn = document.getElementById('submitBtnCaregiver');
    const messageDiv = document.getElementById('formMessageCaregiver');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Enviando...';

    try {
        @if(config('integrations.recaptcha.enabled') && config('integrations.recaptcha.site_key'))
        const token = await grecaptcha.execute('{{ config('integrations.recaptcha.site_key') }}', {action: 'submit_caregiver'});
        document.getElementById('recaptcha_token_caregiver').value = token;
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
            messageDiv.innerHTML = '<div class="card" style="background: #d4edda; border-color: #c3e6cb; color: #155724;">' + data.message + '</div>';
            messageDiv.style.display = 'block';
            form.reset();
        } else {
            messageDiv.innerHTML = '<div class="card" style="background: #f8d7da; border-color: #f5c6cb; color: #721c24;">' + (data.message || 'Erro ao enviar. Tente novamente.') + '</div>';
            messageDiv.style.display = 'block';
        }
    } catch (error) {
        messageDiv.innerHTML = '<div class="card" style="background: #f8d7da; border-color: #f5c6cb; color: #721c24;">Erro ao enviar. Por favor, tente novamente.</div>';
        messageDiv.style.display = 'block';
    }

    submitBtn.disabled = false;
    submitBtn.textContent = 'Enviar cadastro';
});
</script>
@endpush
