<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo(a) ao Carinho com Você</title>
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #2C3E50;
            background-color: #FAFAF8;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #5B8C5A;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .header h1 {
            color: white;
            margin: 0;
            font-size: 24px;
        }
        .content {
            background-color: white;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .highlight-box {
            background-color: #F5E6D3;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #7F8C8D;
            font-size: 12px;
        }
        .button {
            display: inline-block;
            background-color: #5B8C5A;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Carinho com Você</h1>
        </div>

        <div class="content">
            <p class="greeting">Olá, <strong>{{ $nome }}</strong>!</p>

            <p>Seja muito bem-vindo(a) ao <strong>Carinho com Você</strong>!</p>

            <p>Estamos muito felizes em ter você conosco. Nosso compromisso é tornar o cuidado domiciliar simples, humano e confiável.</p>

            <div class="highlight-box">
                <h3 style="margin-top: 0;">O que você pode esperar:</h3>
                <ul>
                    <li>Cuidadores qualificados e avaliados</li>
                    <li>Contratação rápida e sem complicação</li>
                    <li>Atendimento digital com suporte humano</li>
                    <li>Substituição facilitada quando necessário</li>
                </ul>
            </div>

            <p>Se tiver qualquer dúvida, nossa equipe está sempre à disposição para ajudar.</p>

            <p>Com carinho,<br>
            <strong>Equipe Carinho com Você</strong></p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Carinho com Você - Todos os direitos reservados</p>
            <p>Este é um e-mail automático. Por favor, não responda diretamente.</p>
        </div>
    </div>
</body>
</html>
