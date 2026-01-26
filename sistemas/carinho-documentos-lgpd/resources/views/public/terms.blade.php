<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Uso - {{ config('branding.name') }}</title>
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
        .terms-content {
            background-color: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-8);
            box-shadow: var(--shadow);
        }
        .terms-content h2 {
            color: var(--color-primary);
            font-size: var(--font-size-lg);
            margin-top: var(--spacing-6);
        }
        .terms-content h2:first-child {
            margin-top: 0;
        }
        .terms-content p,
        .terms-content ul {
            color: var(--color-text-muted);
        }
        .terms-content ul {
            padding-left: var(--spacing-6);
        }
        .terms-content li {
            margin-bottom: var(--spacing-2);
        }
        .last-updated {
            color: var(--color-text-light);
            font-size: var(--font-size-sm);
            margin-bottom: var(--spacing-6);
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
            <h1>Termos de Uso</h1>
            <p>{{ config('branding.name') }}</p>
        </div>
    </header>

    <main class="content-section">
        <div class="container">
            <div class="terms-content">
                <p class="last-updated">Ultima atualizacao: {{ date('d/m/Y') }}</p>

                <h2>1. ACEITACAO DOS TERMOS</h2>
                <p>
                    Ao utilizar os servicos da Carinho com Voce, voce concorda com estes Termos de Uso.
                    Se voce nao concordar, nao utilize nossos servicos.
                </p>

                <h2>2. DESCRICAO DOS SERVICOS</h2>
                <p>
                    A {{ config('branding.name') }} oferece servicos de intermediacao entre clientes que necessitam
                    de cuidadores domiciliares e profissionais qualificados para prestacao desses servicos.
                </p>

                <h2>3. CADASTRO E CONTA</h2>
                <p>
                    Para utilizar nossos servicos, voce deve fornecer informacoes verdadeiras e completas.
                    Voce e responsavel por manter a confidencialidade de sua conta.
                </p>

                <h2>4. USO ACEITAVEL</h2>
                <p>
                    Voce concorda em utilizar nossos servicos apenas para fins legitimos e de acordo com
                    estes Termos e a legislacao aplicavel.
                </p>

                <h2>5. PROPRIEDADE INTELECTUAL</h2>
                <p>
                    Todo o conteudo disponibilizado e de propriedade da {{ config('branding.name') }} ou de seus
                    licenciadores.
                </p>

                <h2>6. LIMITACAO DE RESPONSABILIDADE</h2>
                <p>
                    A {{ config('branding.name') }} nao se responsabiliza por danos indiretos, incidentais ou
                    consequentes decorrentes do uso de nossos servicos.
                </p>

                <h2>7. ALTERACOES NOS TERMOS</h2>
                <p>
                    Reservamo-nos o direito de modificar estes Termos a qualquer momento. As alteracoes
                    entrarao em vigor apos publicacao.
                </p>

                <h2>8. CONTATO</h2>
                <p>
                    Em caso de duvidas, entre em contato: <a href="mailto:{{ config('branding.email.reply_to') }}">{{ config('branding.email.reply_to') }}</a>
                </p>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>
                <a href="/privacidade">Politica de Privacidade</a> |
                <a href="https://{{ config('branding.domain') }}">{{ config('branding.domain') }}</a>
            </p>
            <p class="text-muted">&copy; {{ date('Y') }} {{ config('branding.name') }}. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>
