<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Como foi seu atendimento?</title>
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
        .button {
            display: inline-block;
            background-color: #5B8C5A;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 16px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #7F8C8D;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Carinho com Você</h1>
        </div>

        <div class="content">
            <p>Olá, <strong>{{ $nome }}</strong>!</p>

            <p>Esperamos que o atendimento com <strong>{{ $cuidador }}</strong> tenha sido excelente!</p>

            <p>Sua opinião é muito importante para nós e nos ajuda a melhorar constantemente nossos serviços.</p>

            <p style="text-align: center;">
                <a href="{{ $link_feedback }}" class="button">Avaliar Atendimento</a>
            </p>

            <p>A avaliação leva menos de 1 minuto e faz toda a diferença!</p>

            <p>Agradecemos por confiar no Carinho com Você.</p>

            <p>Com carinho,<br>
            <strong>Equipe Carinho com Você</strong></p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Carinho com Você - Todos os direitos reservados</p>
        </div>
    </div>
</body>
</html>
