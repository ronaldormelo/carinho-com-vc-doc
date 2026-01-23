@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<section class="section" style="background: var(--color-primary); color: white; padding: var(--spacing-12) 0;">
    <div class="container">
        <h1 style="color: white;">Politica de Pagamento e Comissoes</h1>
        <p style="opacity: 0.9;">Carinho com Voce</p>
    </div>
</section>

{{-- Content --}}
<section class="section">
    <div class="container">
        <div class="legal-content">
            <p class="text-muted">Ultima atualizacao: {{ date('d/m/Y') }}</p>

            <h2>1. POLITICA DE PAGAMENTO</h2>

            <div class="highlight-box" style="background: #d4edda; border-color: var(--color-success);">
                <p style="margin: 0; font-size: var(--font-size-lg);">
                    <strong>O pagamento e sempre ADIANTADO (pre-pago).</strong>
                </p>
            </div>

            <p>{{ $paymentPolicy['description'] }}</p>

            <h3>Formas de Pagamento Aceitas</h3>
            <ul>
                <li><strong>PIX:</strong> Pagamento instantaneo, sem taxas adicionais</li>
                <li><strong>Boleto Bancario:</strong> Prazo de compensacao de ate 2 dias uteis</li>
                <li><strong>Cartao de Credito:</strong> Parcelamento disponivel conforme negociacao</li>
            </ul>

            <h3>Prazo para Pagamento</h3>
            <p>
                O pagamento deve ser confirmado com <strong>{{ $paymentPolicy['advance_hours'] }} horas de antecedencia</strong>
                antes do inicio do servico. Servicos nao serao iniciados sem a confirmacao do pagamento.
            </p>

            <h2>2. ATRASO NO PAGAMENTO</h2>
            <p>Em caso de atraso no pagamento:</p>
            <ul>
                <li><strong>Juros:</strong> {{ number_format($paymentPolicy['late_interest_daily'] * 100, 3) }}% ao dia (aproximadamente 1% ao mes)</li>
                <li><strong>Multa:</strong> {{ number_format($paymentPolicy['late_penalty'], 1) }}% sobre o valor devido</li>
            </ul>
            <p>
                Pagamentos em atraso podem resultar na suspensao dos servicos ate a regularizacao.
            </p>

            <h2>3. COMISSOES DOS CUIDADORES</h2>
            <p>
                {{ $commission['description'] }}
            </p>

            <table class="policy-table">
                <thead>
                    <tr>
                        <th>Tipo de Servico</th>
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
                        <td>Diario</td>
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

            <h3>Bonus para Cuidadores</h3>
            <p>Os cuidadores podem receber bonus adicionais:</p>
            <ul>
                <li><strong>Bonus por avaliacao:</strong> Ate +{{ $commission['bonus']['rating'] }}% para cuidadores com avaliacoes consistentemente altas</li>
                <li><strong>Bonus por tempo de casa:</strong> Ate +{{ $commission['bonus']['tenure'] }}% para cuidadores com mais de 1 ano de parceria</li>
            </ul>

            <h2>4. POLITICA DE REPASSES</h2>
            <p>{{ $payoutPolicy['description'] }}</p>

            <table class="policy-table">
                <tbody>
                    <tr>
                        <td><strong>Frequencia</strong></td>
                        <td>Semanal (todas as sextas-feiras)</td>
                    </tr>
                    <tr>
                        <td><strong>Valor minimo</strong></td>
                        <td>R$ {{ number_format($payoutPolicy['min_value'], 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Liberacao</strong></td>
                        <td>{{ $payoutPolicy['release_days'] }} dias apos conclusao do servico</td>
                    </tr>
                    <tr>
                        <td><strong>Metodo</strong></td>
                        <td>Transferencia bancaria (PIX)</td>
                    </tr>
                </tbody>
            </table>

            <div class="highlight-box">
                <p style="margin: 0;">
                    <strong>Observacao:</strong> Se o valor acumulado nao atingir o minimo de R$ {{ number_format($payoutPolicy['min_value'], 2, ',', '.') }},
                    o saldo sera acumulado para o proximo ciclo de repasse.
                </p>
            </div>

            <h2>5. NOTAS FISCAIS</h2>
            <p>
                A Carinho com Voce emite nota fiscal de servico (NFS-e) para todos os pagamentos realizados.
                A nota fiscal sera enviada por e-mail em ate 5 dias uteis apos a confirmacao do pagamento.
            </p>

            <h2>6. REEMBOLSOS</h2>
            <p>
                Os reembolsos seguem a <a href="{{ route('legal.cancellation') }}">Politica de Cancelamento</a>.
                Prazos para processamento de reembolso:
            </p>
            <ul>
                <li><strong>PIX:</strong> ate 2 dias uteis</li>
                <li><strong>Cartao de credito:</strong> ate 2 faturas</li>
                <li><strong>Boleto:</strong> ate 5 dias uteis via transferencia</li>
            </ul>

            <h2>7. DISPUTAS E CONTESTACOES</h2>
            <p>
                Em caso de divergencias sobre valores ou cobrancas:
            </p>
            <ol>
                <li>Entre em contato pelo WhatsApp ou e-mail</li>
                <li>Informe o numero do contrato e detalhes da contestacao</li>
                <li>Aguarde analise em ate 5 dias uteis</li>
                <li>Receba a resolucao e eventuais ajustes</li>
            </ol>

            <h2>8. CONTATO</h2>
            <p>
                Para questoes sobre pagamentos:
            </p>
            <p>
                <strong>WhatsApp:</strong> {{ config('branding.contact.whatsapp_display') }}<br>
                <strong>E-mail:</strong> <a href="mailto:{{ config('branding.contact.email') }}">{{ config('branding.contact.email') }}</a>
            </p>
        </div>
    </div>
</section>
@endsection
