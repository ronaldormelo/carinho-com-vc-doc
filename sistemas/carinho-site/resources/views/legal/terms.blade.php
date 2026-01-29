@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<section class="section" style="background: var(--color-primary); color: white; padding: var(--spacing-12) 0;">
    <div class="container">
        <h1 style="color: white;">Termos de Uso</h1>
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
                Ao utilizar os serviços da Carinho com Você, você concorda com estes Termos de Uso.
                Se você não concordar com qualquer parte destes termos, não utilize nossos serviços.
            </p>
            <p>
                Estes termos constituem um acordo juridicamente vinculativo entre você e a Carinho com Você.
            </p>

            <h2>2. DESCRIÇÃO DOS SERVIÇOS</h2>
            <p>
                A Carinho com Você oferece serviços de intermediação entre clientes que necessitam
                de cuidadores domiciliares e profissionais qualificados para prestação desses serviços.
            </p>
            <p>Os serviços incluem:</p>
            <ul>
                <li>Captação e qualificação de demandas de cuidado</li>
                <li>Seleção e alocação de cuidadores adequados</li>
                <li>Gestão de contratos e pagamentos</li>
                <li>Suporte operacional durante o atendimento</li>
                <li>Substituição de cuidadores quando necessário</li>
            </ul>

            <h2>3. CADASTRO E CONTA</h2>
            <p>
                Para utilizar nossos serviços, você deve fornecer informações verdadeiras, precisas e completas.
                Você é responsável por:
            </p>
            <ul>
                <li>Manter a confidencialidade de seus dados de acesso</li>
                <li>Todas as atividades realizadas em sua conta</li>
                <li>Atualizar seus dados quando necessário</li>
                <li>Notificar imediatamente qualquer uso não autorizado</li>
            </ul>

            <h2>4. OBRIGAÇÕES DO CLIENTE</h2>
            <p>Ao contratar nossos serviços, o cliente se compromete a:</p>
            <ul>
                <li>Fornecer informações verdadeiras sobre o paciente e suas necessidades</li>
                <li>Efetuar o pagamento conforme acordado (sempre adiantado)</li>
                <li>Tratar o cuidador com respeito e dignidade</li>
                <li>Fornecer ambiente de trabalho adequado e seguro</li>
                <li>Comunicar alterações de agenda com antecedência</li>
                <li>Fornecer feedback honesto após o atendimento</li>
            </ul>

            <h2>5. RELAÇÃO COM CUIDADORES</h2>
            <p>
                Os cuidadores que atuam pela plataforma são profissionais independentes.
                A Carinho com Você atua como intermediária, sendo responsável por:
            </p>
            <ul>
                <li>Verificação básica de documentos e experiência</li>
                <li>Alocação de profissionais adequados ao perfil da demanda</li>
                <li>Gestão de agenda e comunicação</li>
                <li>Processamento de pagamentos e repasses</li>
            </ul>
            <p>
                A Carinho com Você não é empregadora dos cuidadores e não se responsabiliza por
                atos praticados por eles fora do escopo do serviço contratado.
            </p>

            <h2>6. PAGAMENTO</h2>
            <p>
                O pagamento dos serviços é sempre <strong>adiantado</strong>, devendo ser realizado
                com antecedência mínima de 24 horas antes do início do atendimento.
            </p>
            <p>Formas de pagamento aceitas:</p>
            <ul>
                <li>PIX</li>
                <li>Boleto bancário</li>
                <li>Cartão de crédito</li>
            </ul>
            <p>
                Consulte a <a href="{{ route('legal.payment') }}">Política de Pagamento</a> para mais detalhes.
            </p>

            <h2>7. CANCELAMENTO E REEMBOLSO</h2>
            <p>
                Os cancelamentos estão sujeitos à nossa <a href="{{ route('legal.cancellation') }}">Política de Cancelamento</a>.
                Em resumo:
            </p>
            <ul>
                <li>Mais de 24h antes: reembolso total</li>
                <li>Entre 6h e 24h: reembolso de 50%</li>
                <li>Menos de 6h: sem reembolso</li>
            </ul>

            <h2>8. LIMITAÇÃO DE RESPONSABILIDADE</h2>
            <p>A Carinho com Você não se responsabiliza por:</p>
            <ul>
                <li>Danos indiretos, incidentais ou consequentes</li>
                <li>Ações do cuidador fora do escopo contratado</li>
                <li>Informações incorretas fornecidas pelo cliente</li>
                <li>Emergências médicas que exijam atendimento especializado</li>
                <li>Perdas decorrentes de força maior</li>
            </ul>
            <p>
                Nossa responsabilidade total está limitada ao valor pago pelo serviço em questão.
            </p>

            <h2>9. PROPRIEDADE INTELECTUAL</h2>
            <p>
                Todo o conteúdo disponibilizado no site é de propriedade da Carinho com Você ou de seus
                licenciadores, incluindo textos, imagens, logos, design e software.
            </p>
            <p>
                É proibida a reprodução, distribuição ou uso comercial sem autorização prévia por escrito.
            </p>

            <h2>10. USO ACEITÁVEL</h2>
            <p>Você concorda em não:</p>
            <ul>
                <li>Utilizar os serviços para fins ilegais</li>
                <li>Fornecer informações falsas ou enganosas</li>
                <li>Assediar, ameaçar ou discriminar cuidadores ou funcionários</li>
                <li>Tentar burlar os sistemas de pagamento ou segurança</li>
                <li>Utilizar a plataforma para captar cuidadores diretamente</li>
            </ul>

            <h2>11. COMUNICAÇÕES</h2>
            <p>
                Ao utilizar nossos serviços, você concorda em receber comunicações por:
            </p>
            <ul>
                <li>WhatsApp: principal canal de atendimento</li>
                <li>E-mail: propostas, contratos e informações importantes</li>
                <li>SMS: notificações urgentes quando necessário</li>
            </ul>
            <p>
                Você pode optar por não receber comunicações de marketing a qualquer momento.
            </p>

            <h2>12. ALTERAÇÕES NOS TERMOS</h2>
            <p>
                Reservamo-nos o direito de modificar estes Termos a qualquer momento.
                As alterações entrarão em vigor após publicação no site.
            </p>
            <p>
                O uso continuado dos serviços após alterações constitui aceitação dos novos termos.
            </p>

            <h2>13. LEI APLICÁVEL E FORO</h2>
            <p>
                Estes Termos são regidos pelas leis da República Federativa do Brasil.
                Fica eleito o foro da comarca de São Paulo/SP para dirimir quaisquer controvérsias.
            </p>

            <h2>14. CONTATO</h2>
            <p>
                Em caso de dúvidas sobre estes Termos:
            </p>
            <p>
                <strong>E-mail:</strong> <a href="mailto:{{ config('branding.contact.email') }}">{{ config('branding.contact.email') }}</a><br>
                <strong>WhatsApp:</strong> {{ config('branding.contact.whatsapp_display') }}
            </p>
        </div>
    </div>
</section>
@endsection
