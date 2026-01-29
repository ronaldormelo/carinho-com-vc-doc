@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<section class="section" style="background: var(--color-danger); color: white; padding: var(--spacing-12) 0;">
    <div class="container">
        <h1 style="color: white;">Política de Emergências</h1>
        <p style="opacity: 0.9;">Carinho com Você</p>
    </div>
</section>

{{-- Content --}}
<section class="section">
    <div class="container">
        <div class="legal-content">
            <p class="text-muted">Última atualização: {{ date('d/m/Y') }}</p>

            <div class="highlight-box" style="background: #f8d7da; border-color: var(--color-danger);">
                <p style="margin: 0; font-size: var(--font-size-lg);">
                    <strong>Em caso de emergencia medica, ligue imediatamente para o SAMU: 192</strong>
                </p>
            </div>

            <h2>1. CANAIS DE EMERGÊNCIA</h2>
            <p>
                Para situações urgentes durante o atendimento, utilize os seguintes canais:
            </p>

            <table class="policy-table">
                <tbody>
                    @foreach($policy['channels'] as $channel => $description)
                    <tr>
                        <td><strong>{{ ucfirst($channel) }}</strong></td>
                        <td>
                            @if($channel === 'whatsapp')
                            {{ config('branding.contact.whatsapp_display') }} - {{ $description }}
                            @elseif($channel === 'email')
                            <a href="mailto:{{ config('branding.contact.email_emergency') }}">{{ config('branding.contact.email_emergency') }}</a>
                            @else
                            {{ $description }}
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <h2>2. TEMPO DE RESPOSTA</h2>
            <p>Nosso compromisso de tempo de resposta por nivel de urgencia:</p>

            <table class="policy-table">
                <thead>
                    <tr>
                        <th>Nível</th>
                        <th>Tempo de Resposta</th>
                        <th>Exemplos</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background: #f8d7da;">
                        <td><strong style="color: var(--color-danger);">Critico</strong></td>
                        <td><strong>{{ $policy['response_time']['critical'] }}</strong></td>
                        <td>Emergência médica, segurança em risco</td>
                    </tr>
                    <tr style="background: #fff3cd;">
                        <td><strong style="color: var(--color-warning);">Alto</strong></td>
                        <td><strong>{{ $policy['response_time']['high'] }}</strong></td>
                        <td>Ausência de cuidador, atraso significativo</td>
                    </tr>
                    <tr>
                        <td><strong>Medio</strong></td>
                        <td><strong>{{ $policy['response_time']['medium'] }}</strong></td>
                        <td>Problemas no atendimento, dúvidas urgentes</td>
                    </tr>
                </tbody>
            </table>

            <h2>3. TIPOS DE EMERGÊNCIA E AÇÕES</h2>

            @foreach($policy['types'] as $type)
            <div class="card mb-4" style="{{ $type['severity'] === 'critical' ? 'border-left: 4px solid var(--color-danger);' : ($type['severity'] === 'high' ? 'border-left: 4px solid var(--color-warning);' : '') }}">
                <h4 style="margin-top: 0;">{{ $type['type'] }}</h4>
                <p class="text-muted" style="margin-bottom: 0;"><strong>Ação:</strong> {{ $type['action'] }}</p>
            </div>
            @endforeach

            <h2>4. PROCEDIMENTOS EM EMERGÊNCIA MÉDICA</h2>
            <p>Em caso de emergência médica durante o atendimento:</p>
            <ol>
                <li><strong>Ligue imediatamente para o SAMU: 192</strong></li>
                <li>Não tente mover o paciente, exceto se houver risco iminente</li>
                <li>Siga as instruções do atendente do SAMU</li>
                <li>Após estabilizar, notifique o familiar responsável</li>
                <li>Entre em contato com a Carinho com Você para registro</li>
            </ol>

            <div class="highlight-box">
                <p style="margin: 0;">
                    <strong>Importante:</strong> O cuidador é treinado para primeiros socorros básicos,
                    mas não é profissional de saúde. Em caso de dúvida, sempre acione o serviço de emergência.
                </p>
            </div>

            <h2>5. AUSÊNCIA OU ATRASO DO CUIDADOR</h2>
            <p>Se o cuidador não comparecer ou estiver atrasado:</p>
            <ol>
                <li>Entre em contato imediatamente pelo WhatsApp</li>
                <li>Nossa equipe tentará contato com o cuidador</li>
                <li>Caso não haja resposta em 15 minutos, iniciaremos busca por substituto</li>
                <li>Você será informado do status a cada etapa</li>
            </ol>
            <p>
                Em caso de cancelamento pelo cuidador, você terá direito a reembolso total,
                conforme <a href="{{ route('legal.cancellation') }}">Política de Cancelamento</a>.
            </p>

            <h2>6. ESCALONAMENTO</h2>
            <p>Se a emergência não for resolvida no tempo esperado:</p>

            <table class="policy-table">
                <tbody>
                    @foreach($policy['escalation'] as $level => $description)
                    <tr>
                        <td><strong>{{ str_replace('_', ' ', ucfirst($level)) }}</strong></td>
                        <td>{{ $description }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <h2>7. REGISTRO DE OCORRÊNCIAS</h2>
            <p>
                Todas as emergências e ocorrências são registradas em nosso sistema para:
            </p>
            <ul>
                <li>Acompanhamento e resolução adequada</li>
                <li>Melhoria contínua dos processos</li>
                <li>Histórico de atendimento do paciente</li>
                <li>Avaliação de cuidadores</li>
            </ul>

            <h2>8. SLA DE ATENDIMENTO</h2>
            <table class="policy-table">
                <tbody>
                    <tr>
                        <td><strong>Horário comercial</strong></td>
                        <td>{{ $sla['business_hours']['start'] }} - {{ $sla['business_hours']['end'] }}</td>
                    </tr>
                    <tr>
                        <td><strong>Primeira resposta</strong></td>
                        <td>{{ $sla['first_response'] }} minutos</td>
                    </tr>
                    <tr>
                        <td><strong>Resolução simples</strong></td>
                        <td>{{ $sla['resolution'] }} minutos</td>
                    </tr>
                </tbody>
            </table>
            <p class="text-muted">
                Fora do horário comercial, as emergências são atendidas por plantão,
                podendo ter tempo de resposta maior.
            </p>

            <h2>9. CONTATO DE EMERGÊNCIA</h2>
            <div class="highlight-box" style="background: #f8d7da; border-color: var(--color-danger);">
                <p style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-2);">
                    <strong>WhatsApp Emergência:</strong> {{ config('branding.contact.whatsapp_display') }}
                </p>
                <p style="margin: 0;">
                    <strong>E-mail:</strong> <a href="mailto:{{ config('branding.contact.email_emergency') }}">{{ config('branding.contact.email_emergency') }}</a>
                </p>
            </div>

            <h2>10. NÚMEROS ÚTEIS</h2>
            <table class="policy-table">
                <tbody>
                    <tr>
                        <td><strong>SAMU</strong></td>
                        <td>192</td>
                    </tr>
                    <tr>
                        <td><strong>Bombeiros</strong></td>
                        <td>193</td>
                    </tr>
                    <tr>
                        <td><strong>Polícia</strong></td>
                        <td>190</td>
                    </tr>
                    <tr>
                        <td><strong>CVV (apoio emocional)</strong></td>
                        <td>188</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
