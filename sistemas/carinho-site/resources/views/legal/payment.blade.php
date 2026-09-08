@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<section class="section" style="background: var(--color-primary); color: white; padding: var(--spacing-12) 0;">
    <div class="container">
        <h1 style="color: white;">Política de Pagamento e Comissões</h1>
        <p style="opacity: 0.9;">Carinho com Você</p>
    </div>
</section>

{{-- Content --}}
<section class="section">
    <div class="container">
        <div class="legal-content">
            <p class="text-muted">Última atualização: {{ date('d/m/Y') }}</p>

            <h2>1. POLÍTICA DE PAGAMENTO</h2>

            <div class="highlight-box" style="background: #d4edda; border-color: var(--color-success);">
                <p style="margin: 0; font-size: var(--font-size-lg);">
                    <strong>O pagamento é sempre ADIANTADO (pré-pago).</strong>
                </p>
            </div>

            <p>{{ $paymentPolicy['description'] }}</p>

            <h3>Formas de Pagamento Aceitas</h3>
            <ul>
                <li><strong>PIX:</strong> Pagamento instantâneo, sem taxas adicionais</li>
                <li><strong>Boleto Bancário:</strong> Prazo de compensação de até 2 dias úteis</li>
                <li><strong>Cartão de Crédito:</strong> Parcelamento disponível conforme negociação</li>
            </ul>

            <h3>Prazo para Pagamento</h3>
            <p>
                O pagamento deve ser confirmado com <strong>{{ $paymentPolicy['advance_hours'] }} horas de antecedência</strong>
                antes do início do serviço. Serviços não serão iniciados sem a confirmação do pagamento.
            </p>

            <h2>2. ATRASO NO PAGAMENTO</h2>
            <p>Em caso de atraso no pagamento:</p>
            <ul>
                <li><strong>Juros:</strong> {{ number_format($paymentPolicy['late_interest_daily'] * 100, 3) }}% ao dia (aproximadamente 1% ao mês)</li>
                <li><strong>Multa:</strong> {{ number_format($paymentPolicy['late_penalty'], 1) }}% sobre o valor devido</li>
            </ul>
            <p>
                Pagamentos em atraso podem resultar na suspensão dos serviços até a regularização.
            </p>

            <h2>3. COMISSÕES DOS CUIDADORES</h2>
            <p>
                {{ $commission['description'] }}
            </p>

            <table class="policy-table">
                <thead>
                    <tr>
                        <th>Tipo de Serviço</th>
                        <th>Percentual do Cuidador</th>
                        <th>Percentual da Plataforma</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Horista</td>
                        <td><strong>{{ $commission['horista'] }}%</strong></td>
                        <td>{{ 100 - $commission['horista'] }}%</td>
                    </tr>
                    <tr>
                        <td>Diário</td>
                        <td><strong>{{ $commission['diario'] }}%</strong></td>
                        <td>{{ 100 - $commission['diario'] }}%</td>
                    </tr>
                    <tr>
                        <td>Mensal</td>
                        <td><strong>{{ $commission['mensal'] }}%</strong></td>
                        <td>{{ 100 - $commission['mensal'] }}%</td>
                    </tr>
                </tbody>
            </table>

            <h3>Bônus para Cuidadores</h3>
            <p>Os cuidadores podem receber bônus adicionais:</p>
            <ul>
                <li><strong>Bônus por avaliação:</strong> Até +{{ $commission['bonus']['rating'] }}% para cuidadores com avaliações consistentemente altas</li>
                <li><strong>Bônus por tempo de casa:</strong> Até +{{ $commission['bonus']['tenure'] }}% para cuidadores com mais de 1 ano de parceria</li>
            </ul>

            <h2>4. POLÍTICA DE REPASSES</h2>
            <p>{{ $payoutPolicy['description'] }}</p>

            <table class="policy-table">
                <tbody>
                    <tr>
                        <td><strong>Frequência</strong></td>
                        <td>Semanal (todas as sextas-feiras)</td>
                    </tr>
                    <tr>
                        <td><strong>Valor mínimo</strong></td>
                        <td>R$ {{ number_format($payoutPolicy['min_value'], 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Liberação</strong></td>
                        <td>{{ $payoutPolicy['release_days'] }} dias após conclusão do serviço</td>
                    </tr>
                    <tr>
                        <td><strong>Método</strong></td>
                        <td>Transferência bancaria (PIX)</td>
                    </tr>
                </tbody>
            </table>

            <div class="highlight-box">
                <p style="margin: 0;">
                    <strong>Observação:</strong> Se o valor acumulado não atingir o mínimo de R$ {{ number_format($payoutPolicy['min_value'], 2, ',', '.') }},
                    o saldo será acumulado para o próximo ciclo de repasse.
                </p>
            </div>

            <h2>5. NOTAS FISCAIS</h2>
            <p>
                A Carinho com Você emite nota fiscal de serviço (NFS-e) para todos os pagamentos realizados.
                A nota fiscal será enviada por e-mail em até 5 dias úteis após a confirmação do pagamento.
            </p>

            <h2>6. REEMBOLSOS</h2>
            <p>
                Os reembolsos seguem a <a href="{{ route('legal.cancellation') }}">Política de Cancelamento</a>.
                Prazos para processamento de reembolso:
            </p>
            <ul>
                <li><strong>PIX:</strong> até 2 dias úteis</li>
                <li><strong>Cartão de crédito:</strong> até 2 faturas</li>
                <li><strong>Boleto:</strong> até 5 dias úteis via transferência</li>
            </ul>

            <h2>7. DISPUTAS E CONTESTAÇÕES</h2>
            <p>
                Em caso de divergências sobre valores ou cobranças:
            </p>
            <ol>
                <li>Entre em contato pelo WhatsApp ou e-mail</li>
                <li>Informe o número do contrato e detalhes da contestação</li>
                <li>Aguarde análise em até 5 dias úteis</li>
                <li>Receba a resolução e eventuais ajustes</li>
            </ol>

            <h2>8. CONTATO</h2>
            <p>
                Para questões sobre pagamentos:
            </p>
            <p>
                <strong>WhatsApp:</strong> {{ config('branding.contact.whatsapp_display') }}<br>
                <strong>E-mail:</strong> <x-mailto :address="config('branding.contact.email')" />
            </p>
        </div>
    </div>
</section>
@endsection
