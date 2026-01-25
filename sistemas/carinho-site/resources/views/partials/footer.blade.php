<footer class="footer" role="contentinfo">
    <div class="container">
        <div class="footer-grid">
            {{-- Brand --}}
            <div class="footer-brand">
                <a href="{{ route('home') }}" class="logo" aria-label="Página inicial - Carinho com Você">
                    <span>Carinho com Você</span>
                </a>
                <p>{{ config('branding.value_proposition') }}</p>
                <p>
                    <strong>WhatsApp:</strong> {{ config('branding.contact.whatsapp_display') }}<br>
                    <strong>E-mail:</strong> {{ config('branding.contact.email') }}
                </p>
                <p style="margin-top: var(--spacing-4);">
                    <strong>Horário de Atendimento:</strong><br>
                    Seg a Sex: 08h às 20h<br>
                    Sáb: 09h às 18h
                </p>
            </div>

            {{-- Links Institucionais --}}
            <div>
                <h4 class="footer-title">Institucional</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('about') }}">Quem Somos</a></li>
                    <li><a href="{{ route('services') }}">Nossos Serviços</a></li>
                    <li><a href="{{ route('how-it-works') }}">Como Funciona</a></li>
                    <li><a href="{{ route('faq') }}">Perguntas Frequentes</a></li>
                    <li><a href="{{ route('investors') }}">Investidores</a></li>
                    <li><a href="{{ route('contact') }}">Contato</a></li>
                </ul>
            </div>

            {{-- Links para Públicos --}}
            <div>
                <h4 class="footer-title">Para Você</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('clients') }}">Preciso de um Cuidador</a></li>
                    <li><a href="{{ route('caregivers') }}">Quero ser Cuidador</a></li>
                    <li><a href="{{ route('legal.payment') }}">Preços e Pagamento</a></li>
                    <li><a href="{{ route('legal.cancellation') }}">Cancelamento</a></li>
                </ul>
            </div>

            {{-- Links Legais --}}
            <div>
                <h4 class="footer-title">Legal</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('legal.privacy') }}">Política de Privacidade</a></li>
                    <li><a href="{{ route('legal.terms') }}">Termos de Uso</a></li>
                    <li><a href="{{ route('legal.cancellation') }}">Política de Cancelamento</a></li>
                    <li><a href="{{ route('legal.emergency') }}">Política de Emergências</a></li>
                    <li><a href="{{ route('legal.caregiver-terms') }}">Termos para Cuidadores</a></li>
                </ul>
            </div>
        </div>

        {{-- Security badges --}}
        <div style="text-align: center; padding: var(--spacing-6) 0; border-top: 1px solid rgba(255, 255, 255, 0.1); margin-top: var(--spacing-6);">
            <p style="color: var(--color-text-muted); font-size: var(--font-size-sm); margin-bottom: var(--spacing-2);">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle; margin-right: 4px;" aria-hidden="true">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                Site seguro com certificado SSL | Seus dados estão protegidos
            </p>
        </div>

        {{-- Bottom --}}
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} {{ config('branding.name') }}. Todos os direitos reservados.</p>
            <p style="margin-top: var(--spacing-2);">
                {{ config('branding.domain') }}
            </p>
        </div>
    </div>
</footer>
