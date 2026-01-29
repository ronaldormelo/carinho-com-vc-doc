@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<section class="section" style="background: var(--color-primary); color: white; padding: var(--spacing-12) 0;">
    <div class="container">
        <h1 style="color: white;">Termos para Cuidadores</h1>
        <p style="opacity: 0.9;">Carinho com Você</p>
    </div>
</section>

{{-- Content --}}
<section class="section">
    <div class="container">
        <div class="legal-content">
            <p class="text-muted">Última atualização: {{ date('d/m/Y') }}</p>

            <h2>1. ACEITAÇÃO DOS TERMOS</h2>
            <p>
                Ao se cadastrar como cuidador parceiro da Carinho com Você, você concorda com estes Termos.
                Eles regem a relação entre você e a plataforma, estabelecendo direitos e obrigações de ambas as partes.
            </p>

            <h2>2. NATUREZA DA RELAÇÃO</h2>
            <p>
                A Carinho com Você é uma plataforma de intermediação. Os cuidadores parceiros são profissionais
                autônomos ou microempreendedores individuais, não havendo vínculo empregatício com a plataforma.
            </p>
            <p>A relação envolve:</p>
            <ul>
                <li>Cadastro e verificação de perfil</li>
                <li>Indicação de oportunidades de trabalho</li>
                <li>Gestão de agenda e comunicação</li>
                <li>Processamento de pagamentos</li>
                <li>Suporte operacional</li>
            </ul>

            <h2>3. REQUISITOS PARA CADASTRO</h2>
            <p>Para ser um cuidador parceiro, você deve:</p>
            <ul>
                <li>Ter no mínimo 18 anos de idade</li>
                <li>Possuir documentos de identificação válidos (RG, CPF)</li>
                <li>Comprovar experiência mínima de 1 ano como cuidador</li>
                <li>Possuir certificado de curso de cuidador (desejável)</li>
                <li>Não possuir antecedentes criminais graves</li>
                <li>Manter disponibilidade atualizada na plataforma</li>
            </ul>

            <h2>4. PROCESSO DE VERIFICAÇÃO</h2>
            <p>Após o cadastro, seu perfil passará por verificação que inclui:</p>
            <ul>
                <li>Validação de documentos de identidade</li>
                <li>Verificação de experiência e referências</li>
                <li>Avaliação de perfil por nossa equipe</li>
                <li>Assinatura de contrato digital</li>
            </ul>
            <p>
                A Carinho com Você se reserva o direito de recusar ou desativar cadastros que não atendam aos critérios.
            </p>

            <h2>5. OBRIGAÇÕES DO CUIDADOR</h2>
            <p>Ao aceitar um atendimento, você se compromete a:</p>
            <ul>
                <li>Comparecer pontualmente no local e horário combinados</li>
                <li>Realizar check-in e check-out pelo sistema</li>
                <li>Prestar o serviço com zelo, dedicação e respeito</li>
                <li>Seguir as orientações da família e do plano de cuidado</li>
                <li>Registrar atividades realizadas durante o atendimento</li>
                <li>Comunicar imediatamente qualquer intercorrência</li>
                <li>Manter sigilo sobre informações dos clientes</li>
                <li>Zelar pela segurança do paciente</li>
            </ul>

            <h2>6. PROIBIÇÕES</h2>
            <p>É expressamente proibido:</p>
            <ul>
                <li>Captar clientes diretamente, fora da plataforma</li>
                <li>Receber pagamentos diretamente dos clientes</li>
                <li>Deixar o paciente sem supervisão durante o atendimento</li>
                <li>Realizar procedimentos médicos sem habilitação</li>
                <li>Compartilhar dados de clientes com terceiros</li>
                <li>Utilizar celular excessivamente durante o atendimento</li>
                <li>Consumir bebidas alcoólicas ou substâncias ilícitas no trabalho</li>
            </ul>
            <p>
                Violações podem resultar em suspensão ou desligamento da plataforma.
            </p>

            <h2>7. COMISSÕES E PAGAMENTOS</h2>
            <p>Você receberá percentual do valor do serviço conforme o tipo:</p>

            <table class="policy-table">
                <thead>
                    <tr>
                        <th>Tipo de Serviço</th>
                        <th>Seu Percentual</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Horista</td>
                        <td><strong>{{ $commission['horista'] }}%</strong></td>
                    </tr>
                    <tr>
                        <td>Diario</td>
                        <td><strong>{{ $commission['diario'] }}%</strong></td>
                    </tr>
                    <tr>
                        <td>Mensal</td>
                        <td><strong>{{ $commission['mensal'] }}%</strong></td>
                    </tr>
                </tbody>
            </table>

            <p><strong>Bônus:</strong></p>
            <ul>
                <li>Até +{{ $commission['bonus']['rating'] }}% por avaliação alta (média acima de 4.5)</li>
                <li>Até +{{ $commission['bonus']['tenure'] }}% por tempo de casa (após 1 ano)</li>
            </ul>

            <h2>8. REPASSES</h2>
            <table class="policy-table">
                <tbody>
                    <tr>
                        <td><strong>Frequencia</strong></td>
                        <td>Semanal (sextas-feiras)</td>
                    </tr>
                    <tr>
                        <td><strong>Valor mínimo</strong></td>
                        <td>R$ {{ number_format($payoutPolicy['min_value'], 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Liberacao</strong></td>
                        <td>{{ $payoutPolicy['release_days'] }} dias após conclusão do serviço</td>
                    </tr>
                </tbody>
            </table>

            <div class="highlight-box">
                <p style="margin: 0;">
                    <strong>Importante:</strong> Para receber os repasses, você deve manter seus dados bancários
                    atualizados na plataforma. Repasses são feitos exclusivamente via PIX.
                </p>
            </div>

            <h2>9. CANCELAMENTO E FALTAS</h2>
            <p>Se você precisar cancelar um atendimento:</p>
            <ul>
                <li><strong>Mais de 24h antes:</strong> Comunique pelo sistema, sem penalidade</li>
                <li><strong>Menos de 24h:</strong> Comunique imediatamente e justifique</li>
                <li><strong>Não comparecimento (no-show):</strong> Pode resultar em suspensão</li>
            </ul>
            <p>
                Cancelamentos frequentes ou injustificados podem levar à suspensão ou desligamento da plataforma.
            </p>

            <h2>10. AVALIAÇÕES</h2>
            <p>
                Após cada atendimento, o cliente poderá avaliar seu serviço. As avaliações:
            </p>
            <ul>
                <li>São confidenciais (cliente não é identificado)</li>
                <li>Influenciam seu ranking na plataforma</li>
                <li>Podem impactar os bônus recebidos</li>
                <li>São utilizadas para melhoria do serviço</li>
            </ul>
            <p>
                Avaliações consistentemente baixas podem resultar em revisão da parceria.
            </p>

            <h2>11. CONFIDENCIALIDADE</h2>
            <p>
                Você se compromete a manter sigilo sobre todas as informações dos clientes,
                incluindo dados pessoais, condições de saúde e rotinas familiares.
            </p>
            <p>
                A violação de confidencialidade constitui falta grave e pode resultar em
                desligamento imediato e medidas legais cabíveis.
            </p>

            <h2>12. ENCERRAMENTO DA PARCERIA</h2>
            <p>A parceria pode ser encerrada:</p>
            <ul>
                <li><strong>Por você:</strong> a qualquer momento, comunicando com 15 dias de antecedência</li>
                <li><strong>Pela plataforma:</strong> por descumprimento destes termos ou avaliações insatisfatórias</li>
            </ul>
            <p>
                Atendimentos ja agendados devem ser concluidos ou transferidos antes do encerramento.
            </p>

            <h2>13. CONTATO</h2>
            <p>Para dúvidas sobre estes termos ou a parceria:</p>
            <p>
                <strong>WhatsApp Cuidadores:</strong> {{ config('branding.contact.whatsapp_display') }}<br>
                <strong>E-mail:</strong> <a href="mailto:{{ config('branding.contact.email') }}">{{ config('branding.contact.email') }}</a>
            </p>
        </div>
    </div>
</section>
@endsection
