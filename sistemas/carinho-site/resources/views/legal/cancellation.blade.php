@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<section class="section" style="background: var(--color-primary); color: white; padding: var(--spacing-12) 0;">
    <div class="container">
        <h1 style="color: white;">Politica de Cancelamento</h1>
        <p style="opacity: 0.9;">Carinho com Voce</p>
    </div>
</section>

{{-- Content --}}
<section class="section">
    <div class="container">
        <div class="legal-content">
            <p class="text-muted">Ultima atualizacao: {{ date('d/m/Y') }}</p>

            <h2>1. VISAO GERAL</h2>
            <p>
                Entendemos que imprevistos acontecem. Esta politica estabelece as regras para
                cancelamento de servicos contratados e os respectivos reembolsos.
            </p>

            <h2>2. REGRAS DE CANCELAMENTO E REEMBOLSO</h2>

            <table class="policy-table">
                <thead>
                    <tr>
                        <th>Prazo de Cancelamento</th>
                        <th>Reembolso</th>
                        <th>Observacao</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($policy['rules'] as $rule)
                    <tr>
                        <td>{{ $rule['condition'] }}</td>
                        <td><strong>{{ $rule['refund'] }}%</strong></td>
                        <td>{{ $rule['description'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="highlight-box">
                <p style="margin: 0;">
                    <strong>Taxa administrativa:</strong> Aplica-se taxa de {{ $policy['admin_fee'] }}%
                    sobre reembolsos parciais para cobrir custos operacionais.
                </p>
            </div>

            <h2>3. COMO SOLICITAR O CANCELAMENTO</h2>
            <p>Para cancelar um servico agendado:</p>
            <ol>
                <li>Entre em contato pelo WhatsApp: {{ config('branding.contact.whatsapp_display') }}</li>
                <li>Informe o numero do contrato ou agendamento</li>
                <li>Confirme a solicitacao de cancelamento</li>
                <li>Aguarde a confirmacao e instrucoes sobre reembolso (se aplicavel)</li>
            </ol>
            <p>
                O cancelamento e considerado efetivado no momento da confirmacao por nossa equipe.
            </p>

            <h2>4. CANCELAMENTO PELO CUIDADOR</h2>
            <p>
                {{ $policy['caregiver_cancellation'] }}
            </p>
            <p>
                Alem disso, nos comprometemos a buscar um cuidador substituto o mais rapido possivel
                para que voce nao fique sem atendimento.
            </p>

            <h2>5. CANCELAMENTO POR FORCA MAIOR</h2>
            <p>
                Em casos de forca maior (desastres naturais, emergencias de saude publica, etc.),
                os cancelamentos serao avaliados individualmente e podem ter tratamento diferenciado.
            </p>

            <h2>6. PRAZO PARA REEMBOLSO</h2>
            <p>Os reembolsos serao processados da seguinte forma:</p>
            <ul>
                <li><strong>PIX:</strong> ate 2 dias uteis</li>
                <li><strong>Cartao de credito:</strong> ate 2 faturas, dependendo da operadora</li>
                <li><strong>Boleto:</strong> ate 5 dias uteis via transferencia bancaria</li>
            </ul>

            <h2>7. REAGENDAMENTO</h2>
            <p>
                Caso prefira, voce pode optar por reagendar o servico em vez de cancelar.
                O reagendamento:
            </p>
            <ul>
                <li>Nao incorre em taxas se solicitado com mais de 24h de antecedencia</li>
                <li>Esta sujeito a disponibilidade de cuidadores</li>
                <li>Deve ser feito para data dentro de 30 dias</li>
            </ul>

            <h2>8. CANCELAMENTO DE PLANOS MENSAIS</h2>
            <p>
                Para servicos contratados em regime mensal:
            </p>
            <ul>
                <li>A solicitacao de cancelamento deve ser feita com 15 dias de antecedencia</li>
                <li>Servicos ja prestados no mes serao cobrados proporcionalmente</li>
                <li>Nao ha multa por cancelamento antecipado</li>
            </ul>

            <h2>9. NAO COMPARECIMENTO (NO-SHOW)</h2>
            <p>
                Se o cliente nao estiver disponivel no momento combinado para o inicio do servico:
            </p>
            <ul>
                <li>O cuidador aguardara por ate 30 minutos</li>
                <li>Apos esse prazo, o servico sera considerado prestado</li>
                <li>Nao havera reembolso neste caso</li>
            </ul>

            <h2>10. CONTATO</h2>
            <p>
                Para cancelamentos ou duvidas sobre esta politica:
            </p>
            <p>
                <strong>WhatsApp:</strong> {{ config('branding.contact.whatsapp_display') }}<br>
                <strong>E-mail:</strong> <a href="mailto:{{ config('branding.contact.email') }}">{{ config('branding.contact.email') }}</a>
            </p>

            <div class="highlight-box" style="background: #fff3cd; border-color: var(--color-warning);">
                <p style="margin: 0;">
                    <strong>Importante:</strong> Recomendamos sempre confirmar o recebimento da solicitacao de cancelamento
                    para garantir que sua requisicao foi processada corretamente.
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
