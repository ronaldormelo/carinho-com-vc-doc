@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<section class="section" style="background: var(--color-primary); color: white; padding: var(--spacing-12) 0;">
    <div class="container">
        <h1 style="color: white;">Politica de Privacidade</h1>
        <p style="opacity: 0.9;">Carinho com Voce</p>
    </div>
</section>

{{-- Content --}}
<section class="section">
    <div class="container">
        <div class="legal-content">
            <p class="text-muted">Ultima atualizacao: {{ date('d/m/Y') }}</p>

            <h2>1. INTRODUCAO</h2>
            <p>
                A Carinho com Voce esta comprometida em proteger sua privacidade. Esta politica
                descreve como coletamos, usamos e protegemos seus dados pessoais em conformidade
                com a Lei Geral de Protecao de Dados (LGPD - Lei no 13.709/2018).
            </p>

            <h2>2. DADOS COLETADOS</h2>
            <p>Coletamos os seguintes dados pessoais:</p>
            <ul>
                <li><strong>Dados de identificacao:</strong> nome completo, CPF, RG, data de nascimento</li>
                <li><strong>Dados de contato:</strong> endereco, telefone, e-mail, WhatsApp</li>
                <li><strong>Dados de saude:</strong> informacoes sobre condicoes de saude do paciente (quando aplicavel e com consentimento especifico)</li>
                <li><strong>Dados profissionais:</strong> formacao, experiencia, certificacoes (para cuidadores)</li>
                <li><strong>Dados de navegacao:</strong> IP, cookies, origem do acesso (UTM)</li>
            </ul>

            <h2>3. FINALIDADE DO TRATAMENTO</h2>
            <p>Utilizamos seus dados para:</p>
            <ul>
                <li>Prestacao dos servicos contratados de cuidado domiciliar</li>
                <li>Comunicacao sobre servicos, atualizacoes e informacoes relevantes</li>
                <li>Match entre clientes e cuidadores adequados</li>
                <li>Cumprimento de obrigacoes legais e contratuais</li>
                <li>Melhoria continua de nossos servicos</li>
                <li>Analise de origem de leads e efetividade de campanhas</li>
            </ul>

            <h2>4. BASE LEGAL</h2>
            <p>O tratamento de dados e realizado com base em:</p>
            <ul>
                <li><strong>Consentimento do titular:</strong> para dados de saude e marketing</li>
                <li><strong>Execucao de contrato:</strong> para prestacao dos servicos</li>
                <li><strong>Cumprimento de obrigacao legal:</strong> para obrigacoes fiscais e trabalhistas</li>
                <li><strong>Interesse legitimo:</strong> para melhoria de servicos e seguranca</li>
            </ul>

            <h2>5. COMPARTILHAMENTO DE DADOS</h2>
            <p>Seus dados podem ser compartilhados com:</p>
            <ul>
                <li><strong>Cuidadores:</strong> informacoes necessarias para prestacao do servico</li>
                <li><strong>Prestadores de servicos de tecnologia:</strong> hospedagem, comunicacao (WhatsApp)</li>
                <li><strong>Processadores de pagamento:</strong> para efetivacao de transacoes</li>
                <li><strong>Autoridades:</strong> quando exigido por lei ou ordem judicial</li>
            </ul>
            <p>
                Nao vendemos ou comercializamos seus dados pessoais com terceiros para fins de marketing.
            </p>

            <h2>6. SEUS DIREITOS</h2>
            <div class="highlight-box">
                <p><strong>Conforme a LGPD, voce tem direito a:</strong></p>
                <ul>
                    <li>Confirmar a existencia de tratamento de seus dados</li>
                    <li>Acessar seus dados pessoais</li>
                    <li>Corrigir dados incompletos, inexatos ou desatualizados</li>
                    <li>Solicitar a anonimizacao, bloqueio ou eliminacao de dados desnecessarios</li>
                    <li>Solicitar a portabilidade dos dados</li>
                    <li>Solicitar a eliminacao dos dados tratados com consentimento</li>
                    <li>Revogar o consentimento a qualquer momento</li>
                    <li>Obter informacoes sobre o compartilhamento de dados</li>
                </ul>
            </div>

            <h2>7. SEGURANCA DOS DADOS</h2>
            <p>
                Implementamos medidas tecnicas e organizacionais para proteger seus dados, incluindo:
            </p>
            <ul>
                <li>Criptografia de dados sensiveis em transito e em repouso</li>
                <li>Controle de acesso baseado em funcao</li>
                <li>Monitoramento e auditoria de acessos</li>
                <li>Backups regulares com armazenamento seguro</li>
                <li>Treinamento de equipe em seguranca da informacao</li>
            </ul>

            <h2>8. RETENCAO DE DADOS</h2>
            <p>
                Mantemos seus dados pelo periodo necessario para cumprir as finalidades descritas ou conforme exigido por lei:
            </p>
            <ul>
                <li><strong>Dados de clientes:</strong> durante a vigencia do contrato + 5 anos</li>
                <li><strong>Dados de cuidadores:</strong> durante a vigencia da parceria + 5 anos</li>
                <li><strong>Dados de leads:</strong> 2 anos apos ultimo contato</li>
                <li><strong>Dados fiscais:</strong> 5 anos conforme legislacao</li>
            </ul>

            <h2>9. COOKIES E TECNOLOGIAS SIMILARES</h2>
            <p>
                Utilizamos cookies para melhorar sua experiencia no site, incluindo:
            </p>
            <ul>
                <li><strong>Cookies essenciais:</strong> necessarios para funcionamento do site</li>
                <li><strong>Cookies de analytics:</strong> para entender como o site e utilizado</li>
                <li><strong>Cookies de marketing:</strong> para rastrear origem do acesso (UTM)</li>
            </ul>
            <p>Voce pode gerenciar suas preferencias de cookies nas configuracoes do seu navegador.</p>

            <h2>10. TRANSFERENCIA INTERNACIONAL</h2>
            <p>
                Alguns de nossos prestadores de servicos (hospedagem, analytics) podem estar localizados fora do Brasil.
                Nesses casos, garantimos que a transferencia seja realizada em conformidade com a LGPD.
            </p>

            <h2>11. CONTATO DO ENCARREGADO (DPO)</h2>
            <p>
                Para exercer seus direitos ou esclarecer duvidas sobre privacidade e protecao de dados:
            </p>
            <p>
                <strong>E-mail:</strong> <a href="mailto:{{ config('branding.contact.email_privacy') }}">{{ config('branding.contact.email_privacy') }}</a>
            </p>
            <p>
                Nos comprometemos a responder suas solicitacoes no prazo de 15 dias, conforme previsto na LGPD.
            </p>

            <h2>12. ALTERACOES NESTA POLITICA</h2>
            <p>
                Esta politica pode ser atualizada periodicamente. Recomendamos sua revisao regular.
                Alteracoes significativas serao comunicadas por e-mail ou pelo site.
            </p>
        </div>
    </div>
</section>
@endsection
