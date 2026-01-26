<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politica de Privacidade - {{ config('branding.name') }}</title>
    <link rel="icon" href="{{ asset(config('branding.assets.logo.favicon', '/images/favicon.ico')) }}" type="image/x-icon">
    <link rel="stylesheet" href="/css/brand.css">
    <style>
        .page-header {
            background-color: var(--color-primary);
            color: white;
            padding: var(--spacing-12) 0;
            text-align: center;
        }
        .page-header h1 {
            color: white;
            margin: 0;
        }
        .content-section {
            padding: var(--spacing-10) 0;
        }
        .privacy-content {
            background-color: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-8);
            box-shadow: var(--shadow);
        }
        .privacy-content h2 {
            color: var(--color-primary);
            font-size: var(--font-size-lg);
            margin-top: var(--spacing-6);
        }
        .privacy-content h2:first-child {
            margin-top: 0;
        }
        .privacy-content p,
        .privacy-content ul {
            color: var(--color-text-muted);
        }
        .privacy-content ul {
            padding-left: var(--spacing-6);
        }
        .privacy-content li {
            margin-bottom: var(--spacing-2);
        }
        .last-updated {
            color: var(--color-text-light);
            font-size: var(--font-size-sm);
            margin-bottom: var(--spacing-6);
        }
        .highlight-box {
            background-color: var(--bg-secondary);
            border-left: 4px solid var(--color-primary);
            padding: var(--spacing-4);
            margin: var(--spacing-4) 0;
        }
        footer {
            background-color: var(--bg-primary);
            border-top: 1px solid var(--border-color);
            padding: var(--spacing-6) 0;
            text-align: center;
        }
        footer a {
            color: var(--color-primary);
        }
    </style>
</head>
<body>
    <header class="page-header">
        <div class="container">
            <div style="margin-bottom: var(--spacing-6);">
                <img src="{{ asset(config('branding.assets.logo.white')) }}" alt="{{ config('branding.name') }}" style="height: 50px;" />
            </div>
            <h1>Politica de Privacidade</h1>
            <p>{{ config('branding.name') }}</p>
        </div>
    </header>

    <main class="content-section">
        <div class="container">
            <div class="privacy-content">
                <p class="last-updated">Ultima atualizacao: {{ date('d/m/Y') }}</p>

                <h2>1. INTRODUCAO</h2>
                <p>
                    A {{ config('branding.name') }} esta comprometida em proteger sua privacidade. Esta politica
                    descreve como coletamos, usamos e protegemos seus dados pessoais em conformidade
                    com a Lei Geral de Protecao de Dados (LGPD - Lei no 13.709/2018).
                </p>

                <h2>2. DADOS COLETADOS</h2>
                <p>Coletamos os seguintes dados pessoais:</p>
                <ul>
                    <li><strong>Dados de identificacao:</strong> nome, CPF, RG, data de nascimento</li>
                    <li><strong>Dados de contato:</strong> endereco, telefone, e-mail</li>
                    <li><strong>Dados de saude:</strong> informacoes sobre condicoes de saude do paciente (quando aplicavel)</li>
                    <li><strong>Dados profissionais:</strong> formacao, experiencia, certificacoes (para cuidadores)</li>
                </ul>

                <h2>3. FINALIDADE DO TRATAMENTO</h2>
                <p>Utilizamos seus dados para:</p>
                <ul>
                    <li>Prestacao dos servicos contratados</li>
                    <li>Comunicacao sobre servicos e atualizacoes</li>
                    <li>Cumprimento de obrigacoes legais</li>
                    <li>Melhoria de nossos servicos</li>
                </ul>

                <h2>4. BASE LEGAL</h2>
                <p>O tratamento de dados e realizado com base em:</p>
                <ul>
                    <li>Consentimento do titular</li>
                    <li>Execucao de contrato</li>
                    <li>Cumprimento de obrigacao legal</li>
                    <li>Interesse legitimo da empresa</li>
                </ul>

                <h2>5. COMPARTILHAMENTO DE DADOS</h2>
                <p>Seus dados podem ser compartilhados com:</p>
                <ul>
                    <li>Cuidadores (para prestacao do servico)</li>
                    <li>Prestadores de servicos de tecnologia</li>
                    <li>Autoridades, quando exigido por lei</li>
                </ul>

                <h2>6. SEUS DIREITOS</h2>
                <div class="highlight-box">
                    <p><strong>Conforme a LGPD, voce tem direito a:</strong></p>
                    <ul>
                        <li>Acessar seus dados pessoais</li>
                        <li>Corrigir dados incompletos ou desatualizados</li>
                        <li>Solicitar a exclusao de seus dados</li>
                        <li>Revogar consentimentos</li>
                        <li>Solicitar portabilidade dos dados</li>
                    </ul>
                </div>

                <h2>7. SEGURANCA</h2>
                <p>
                    Implementamos medidas tecnicas e organizacionais para proteger seus dados,
                    incluindo criptografia, controle de acesso e monitoramento.
                </p>

                <h2>8. RETENCAO DE DADOS</h2>
                <p>
                    Mantemos seus dados pelo periodo necessario para cumprir as finalidades
                    descritas ou conforme exigido por lei.
                </p>

                <h2>9. CONTATO DO ENCARREGADO (DPO)</h2>
                <p>
                    Para exercer seus direitos ou esclarecer duvidas sobre privacidade:
                </p>
                <p>
                    <strong>E-mail:</strong> <a href="mailto:{{ config('branding.email.reply_to') }}">{{ config('branding.email.reply_to') }}</a>
                </p>

                <h2>10. ALTERACOES</h2>
                <p>
                    Esta politica pode ser atualizada periodicamente. Recomendamos sua revisao regular.
                </p>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>
                <a href="/termos">Termos de Uso</a> |
                <a href="https://{{ config('branding.domain') }}">{{ config('branding.domain') }}</a>
            </p>
            <p class="text-muted">&copy; {{ date('Y') }} {{ config('branding.name') }}. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>
