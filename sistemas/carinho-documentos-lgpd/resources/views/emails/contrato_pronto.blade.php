<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato Pronto para Assinatura</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #1F2933;
            background-color: #F4F7F9;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .card {
            background-color: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(31, 41, 51, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #5BBFAD;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            color: #FFFFFF;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 25px;
            color: #616E7C;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background-color: #5BBFAD;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #4AA89A;
        }
        .btn-container {
            text-align: center;
            margin: 30px 0;
        }
        .info-box {
            background-color: #F4F7F9;
            border-radius: 8px;
            padding: 20px;
            margin-top: 25px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #1F2933;
            font-size: 14px;
            font-weight: 600;
        }
        .info-box ul {
            margin: 0;
            padding-left: 20px;
            color: #616E7C;
            font-size: 14px;
        }
        .info-box li {
            margin-bottom: 5px;
        }
        .footer {
            padding: 25px 30px;
            background-color: #F4F7F9;
            text-align: center;
            font-size: 13px;
            color: #9AA5B1;
        }
        .footer a {
            color: #5BBFAD;
            text-decoration: none;
        }
        .expiration {
            background-color: #FFF8E1;
            border-left: 4px solid #D69E2E;
            padding: 12px 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #744210;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>{{ $brandName }}</h1>
            </div>
            <div class="content">
                <p class="greeting">Ola, {{ $recipientName }}!</p>

                <p class="message">
                    Seu contrato com a {{ $brandName }} esta pronto para assinatura digital.
                    Clique no botao abaixo para revisar e assinar o documento.
                </p>

                <div class="btn-container">
                    <a href="{{ $signatureUrl }}" class="btn">Assinar Contrato</a>
                </div>

                <div class="expiration">
                    Este link e valido por 72 horas. Apos esse periodo, sera necessario solicitar um novo link.
                </div>

                <div class="info-box">
                    <h3>Como funciona:</h3>
                    <ul>
                        <li>Clique no botao acima para acessar o contrato</li>
                        <li>Leia atentamente todas as clausulas</li>
                        <li>Confirme sua identidade com o codigo enviado</li>
                        <li>Receba uma copia assinada por email</li>
                    </ul>
                </div>
            </div>
            <div class="footer">
                <p>
                    Em caso de duvidas, entre em contato conosco pelo email
                    <a href="mailto:contato@carinho.com.vc">contato@carinho.com.vc</a>
                </p>
                <p>&copy; {{ date('Y') }} {{ $brandName }}. Todos os direitos reservados.</p>
            </div>
        </div>
    </div>
</body>
</html>
