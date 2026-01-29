@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<section class="section" style="background: var(--color-primary); color: white; padding: var(--spacing-12) 0;">
    <div class="container">
        <h1 style="color: white;">Política de Cancelamento</h1>
        <p style="opacity: 0.9;">Carinho com Você</p>
    </div>
</section>

{{-- Content --}}
<section class="section">
    <div class="container">
        <div class="legal-content">
            <p class="text-muted">Última atualização: {{ date('d/m/Y') }}</p>

            <h2>1. VISÃO GERAL</h2>
            <p>
                Entendemos que imprevistos acontecem. Esta política estabelece as regras para
                cancelamento de serviços contratados e os respectivos reembolsos.
            </p>

            <h2>2. REGRAS DE CANCELAMENTO E REEMBOLSO</h2>

            <table class="policy-table">
                <thead>
                    <tr>
                        <th>Prazo de Cancelamento</th>
                        <th>Reembolso</th>
                        <th>Observação</th>
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
            <p>Para cancelar um serviço agendado:</p>
            <ol>
                <li>Entre em contato pelo WhatsApp: {{ config('branding.contact.whatsapp_display') }}</li>
                <li>Informe o número do contrato ou agendamento</li>
                <li>Confirme a solicitação de cancelamento</li>
                <li>Aguarde a confirmação e instruções sobre reembolso (se aplicável)</li>
            </ol>
            <p>
                O cancelamento é considerado efetivado no momento da confirmação por nossa equipe.
            </p>

            <h2>4. CANCELAMENTO PELO CUIDADOR</h2>
            <p>
                {{ $policy['caregiver_cancellation'] }}
            </p>
            <p>
                Alem disso, nos comprometemos a buscar um cuidador substituto o mais rapido possivel
                para que voce nao fique sem atendimento.
            </p>

            <h2>5. CANCELAMENTO POR FORÇA MAIOR</h2>
            <p>
                Em casos de força maior (desastres naturais, emergências de saúde pública, etc.),
                os cancelamentos serão avaliados individualmente e podem ter tratamento diferenciado.
            </p>

            <h2>6. PRAZO PARA REEMBOLSO</h2>
            <p>Os reembolsos serão processados da seguinte forma:</p>
            <ul>
                <li><strong>PIX:</strong> até 2 dias úteis</li>
                <li><strong>Cartão de crédito:</strong> até 2 faturas, dependendo da operadora</li>
                <li><strong>Boleto:</strong> até 5 dias úteis via transferência bancária</li>
            </ul>

            <h2>7. REAGENDAMENTO</h2>
            <p>
                Caso prefira, você pode optar por reagendar o serviço em vez de cancelar.
                O reagendamento:
            </p>
            <ul>
                <li>Não incorre em taxas se solicitado com mais de 24h de antecedência</li>
                <li>Está sujeito à disponibilidade de cuidadores</li>
                <li>Deve ser feito para data dentro de 30 dias</li>
            </ul>

            <h2>8. CANCELAMENTO DE PLANOS MENSAIS</h2>
            <p>
                Para serviços contratados em regime mensal:
            </p>
            <ul>
                <li>A solicitação de cancelamento deve ser feita com 15 dias de antecedência</li>
                <li>Serviços já prestados no mês serão cobrados proporcionalmente</li>
                <li>Não há multa por cancelamento antecipado</li>
            </ul>

            <h2>9. NÃO COMPARECIMENTO (NO-SHOW)</h2>
            <p>
                Se o cliente não estiver disponível no momento combinado para o início do serviço:
            </p>
            <ul>
                <li>O cuidador aguardará por até 30 minutos</li>
                <li>Após esse prazo, o serviço será considerado prestado</li>
                <li>Não haverá reembolso neste caso</li>
            </ul>

            <h2>10. CONTATO</h2>
            <p>
                Para cancelamentos ou dúvidas sobre esta política:
            </p>
            <p>
                <strong>WhatsApp:</strong> {{ config('branding.contact.whatsapp_display') }}<br>
                <strong>E-mail:</strong> <a href="mailto:{{ config('branding.contact.email') }}">{{ config('branding.contact.email') }}</a>
            </p>

            <div class="highlight-box" style="background: #fff3cd; border-color: var(--color-warning);">
                <p style="margin: 0;">
                    <strong>Importante:</strong> Recomendamos sempre confirmar o recebimento da solicitação de cancelamento
                    para garantir que sua requisição foi processada corretamente.
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
