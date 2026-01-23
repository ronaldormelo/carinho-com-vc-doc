<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Confirmado</title>
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
        .success-icon {
            text-align: center;
            font-size: 48px;
            margin: 20px 0;
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
            <div class="success-icon">✓</div>

            <p>Olá, <strong>{{ $nome }}</strong>!</p>

            <p>Seu cadastro foi realizado com sucesso!</p>

            @if($tipo === 'client')
            <p>Agora você pode começar a buscar o cuidador ideal para suas necessidades. Nossa equipe está à disposição para ajudar em todo o processo.</p>
            @else
            <p>Bem-vindo(a) à nossa rede de cuidadores! Em breve você receberá mais informações sobre os próximos passos.</p>
            @endif

            <p>Com carinho,<br>
            <strong>Equipe Carinho com Você</strong></p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Carinho com Você - Todos os direitos reservados</p>
        </div>
    </div>
</body>
</html>
