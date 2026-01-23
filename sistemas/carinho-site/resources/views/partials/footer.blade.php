<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            {{-- Brand --}}
            <div class="footer-brand">
                <a href="{{ route('home') }}" class="logo">
                    <span>Carinho com Voce</span>
                </a>
                <p>{{ config('branding.value_proposition') }}</p>
                <p>
                    <strong>WhatsApp:</strong> {{ config('branding.contact.whatsapp_display') }}<br>
                    <strong>E-mail:</strong> {{ config('branding.contact.email') }}
                </p>
            </div>

            {{-- Links Institucionais --}}
            <div>
                <h4 class="footer-title">Institucional</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('about') }}">Quem Somos</a></li>
                    <li><a href="{{ route('services') }}">Nossos Servicos</a></li>
                    <li><a href="{{ route('how-it-works') }}">Como Funciona</a></li>
                    <li><a href="{{ route('faq') }}">Perguntas Frequentes</a></li>
                    <li><a href="{{ route('contact') }}">Contato</a></li>
                </ul>
            </div>

            {{-- Links para Publicos --}}
            <div>
                <h4 class="footer-title">Para Voce</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('clients') }}">Preciso de um Cuidador</a></li>
                    <li><a href="{{ route('caregivers') }}">Quero ser Cuidador</a></li>
                    <li><a href="{{ route('legal.payment') }}">Precos e Pagamento</a></li>
                    <li><a href="{{ route('legal.cancellation') }}">Cancelamento</a></li>
                </ul>
            </div>

            {{-- Links Legais --}}
            <div>
                <h4 class="footer-title">Legal</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('legal.privacy') }}">Politica de Privacidade</a></li>
                    <li><a href="{{ route('legal.terms') }}">Termos de Uso</a></li>
                    <li><a href="{{ route('legal.cancellation') }}">Politica de Cancelamento</a></li>
                    <li><a href="{{ route('legal.emergency') }}">Politica de Emergencias</a></li>
                    <li><a href="{{ route('legal.caregiver-terms') }}">Termos para Cuidadores</a></li>
                </ul>
            </div>
        </div>

        {{-- Bottom --}}
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} {{ config('branding.name') }}. Todos os direitos reservados.</p>
            <p style="margin-top: var(--spacing-2);">
                CNPJ: 00.000.000/0001-00 | {{ config('branding.domain') }}
            </p>
        </div>
    </div>
</footer>
