@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<section class="section" style="background: var(--color-primary); color: white; padding: var(--spacing-12) 0;">
    <div class="container">
        <h1 style="color: white;">Política de Privacidade</h1>
        <p style="opacity: 0.9;">Carinho com Você</p>
    </div>
</section>

{{-- Content --}}
<section class="section">
    <div class="container">
        <div class="legal-content">
            <p class="text-muted">Última atualização: {{ date('d/m/Y') }}</p>

            <h2>1. INTRODUÇÃO</h2>
            <p>
                A Carinho com Você está comprometida em proteger sua privacidade. Esta política
                descreve como coletamos, usamos e protegemos seus dados pessoais em conformidade
                com a Lei Geral de Proteção de Dados (LGPD - Lei nº 13.709/2018).
            </p>

            <h2>2. DADOS COLETADOS</h2>
            <p>Coletamos os seguintes dados pessoais:</p>
            <ul>
                <li><strong>Dados de identificação:</strong> nome completo, CPF, RG, data de nascimento</li>
                <li><strong>Dados de contato:</strong> endereço, telefone, e-mail, WhatsApp</li>
                <li><strong>Dados de saúde:</strong> informações sobre condições de saúde do paciente (quando aplicável e com consentimento específico)</li>
                <li><strong>Dados profissionais:</strong> formação, experiência, certificações (para cuidadores)</li>
                <li><strong>Dados de navegação:</strong> IP, cookies, origem do acesso (UTM)</li>
            </ul>

            <h2>3. FINALIDADE DO TRATAMENTO</h2>
            <p>Utilizamos seus dados para:</p>
            <ul>
                <li>Prestação dos serviços contratados de cuidado domiciliar</li>
                <li>Comunicação sobre serviços, atualizações e informações relevantes</li>
                <li>Match entre clientes e cuidadores adequados</li>
                <li>Cumprimento de obrigações legais e contratuais</li>
                <li>Melhoria contínua de nossos serviços</li>
                <li>Análise de origem de leads e efetividade de campanhas</li>
            </ul>

            <h2>4. BASE LEGAL</h2>
            <p>O tratamento de dados é realizado com base em:</p>
            <ul>
                <li><strong>Consentimento do titular:</strong> para dados de saúde e marketing</li>
                <li><strong>Execução de contrato:</strong> para prestação dos serviços</li>
                <li><strong>Cumprimento de obrigação legal:</strong> para obrigações fiscais e trabalhistas</li>
                <li><strong>Interesse legítimo:</strong> para melhoria de serviços e segurança</li>
            </ul>

            <h2>5. COMPARTILHAMENTO DE DADOS</h2>
            <p>Seus dados podem ser compartilhados com:</p>
            <ul>
                <li><strong>Cuidadores:</strong> informações necessárias para prestação do serviço</li>
                <li><strong>Prestadores de serviços de tecnologia:</strong> hospedagem, comunicação (WhatsApp)</li>
                <li><strong>Processadores de pagamento:</strong> para efetivação de transações</li>
                <li><strong>Autoridades:</strong> quando exigido por lei ou ordem judicial</li>
            </ul>
            <p>
                Não vendemos ou comercializamos seus dados pessoais com terceiros para fins de marketing.
            </p>

            <h2>6. SEUS DIREITOS</h2>
            <div class="highlight-box">
                <p><strong>Conforme a LGPD, você tem direito a:</strong></p>
                <ul>
                    <li>Confirmar a existência de tratamento de seus dados</li>
                    <li>Acessar seus dados pessoais</li>
                    <li>Corrigir dados incompletos, inexatos ou desatualizados</li>
                    <li>Solicitar a anonimização, bloqueio ou eliminação de dados desnecessários</li>
                    <li>Solicitar a portabilidade dos dados</li>
                    <li>Solicitar a eliminação dos dados tratados com consentimento</li>
                    <li>Revogar o consentimento a qualquer momento</li>
                    <li>Obter informações sobre o compartilhamento de dados</li>
                </ul>
            </div>

            <h2>7. SEGURANÇA DOS DADOS</h2>
            <p>
                Implementamos medidas técnicas e organizacionais para proteger seus dados, incluindo:
            </p>
            <ul>
                <li>Criptografia de dados sensíveis em trânsito e em repouso</li>
                <li>Controle de acesso baseado em função</li>
                <li>Monitoramento e auditoria de acessos</li>
                <li>Backups regulares com armazenamento seguro</li>
                <li>Treinamento de equipe em segurança da informação</li>
            </ul>

            <h2>8. RETENÇÃO DE DADOS</h2>
            <p>
                Mantemos seus dados pelo período necessário para cumprir as finalidades descritas ou conforme exigido por lei:
            </p>
            <ul>
                <li><strong>Dados de clientes:</strong> durante a vigência do contrato + 5 anos</li>
                <li><strong>Dados de cuidadores:</strong> durante a vigência da parceria + 5 anos</li>
                <li><strong>Dados de leads:</strong> 2 anos após último contato</li>
                <li><strong>Dados fiscais:</strong> 5 anos conforme legislação</li>
            </ul>

            <h2>9. COOKIES E TECNOLOGIAS SIMILARES</h2>
            <p>
                Utilizamos cookies para melhorar sua experiência no site, incluindo:
            </p>
            <ul>
                <li><strong>Cookies essenciais:</strong> necessários para funcionamento do site</li>
                <li><strong>Cookies de analytics:</strong> para entender como o site é utilizado</li>
                <li><strong>Cookies de marketing:</strong> para rastrear origem do acesso (UTM)</li>
            </ul>
            <p>Você pode gerenciar suas preferências de cookies nas configurações do seu navegador.</p>

            <h2>10. TRANSFERÊNCIA INTERNACIONAL</h2>
            <p>
                Alguns de nossos prestadores de serviços (hospedagem, analytics) podem estar localizados fora do Brasil.
                Nesses casos, garantimos que a transferência seja realizada em conformidade com a LGPD.
            </p>

            <h2>11. CONTATO DO ENCARREGADO (DPO)</h2>
            <p>
                Para exercer seus direitos ou esclarecer dúvidas sobre privacidade e proteção de dados:
            </p>
            <p>
                <strong>E-mail:</strong> <a href="mailto:{{ config('branding.contact.email_privacy') }}">{{ config('branding.contact.email_privacy') }}</a>
            </p>
            <p>
                Nos comprometemos a responder suas solicitações no prazo de 15 dias, conforme previsto na LGPD.
            </p>

            <h2>12. ALTERAÇÕES NESTA POLÍTICA</h2>
            <p>
                Esta política pode ser atualizada periodicamente. Recomendamos sua revisão regular.
                Alterações significativas serão comunicadas por e-mail ou pelo site.
            </p>
        </div>
    </div>
</section>
@endsection
