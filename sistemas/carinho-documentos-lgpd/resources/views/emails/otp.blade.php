<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codigo de Verificacao</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #1a2b32;
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
            text-align: center;
        }
        .message {
            margin-bottom: 25px;
            color: #616E7C;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #325B5D;
            padding: 20px;
            background-color: #F4F7F9;
            border-radius: 8px;
            margin: 25px 0;
        }
        .expiration {
            font-size: 14px;
            color: #D69E2E;
            margin-top: 20px;
        }
        .warning {
            font-size: 13px;
            color: #9AA5B1;
            margin-top: 25px;
            padding: 15px;
            background-color: #F4F7F9;
            border-radius: 8px;
        }
        .footer {
            padding: 25px 30px;
            background-color: #F4F7F9;
            text-align: center;
            font-size: 13px;
            color: #9AA5B1;
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
                <p class="message">
                    Seu codigo de verificacao para assinatura digital e:
                </p>

                <div class="otp-code">{{ $code }}</div>

                <p class="expiration">
                    Este codigo e valido por {{ $expirationMinutes }} minutos.
                </p>

                <div class="warning">
                    Se voce nao solicitou este codigo, ignore este email.
                    Nao compartilhe este codigo com ninguem.
                </div>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ $brandName }}. Todos os direitos reservados.</p>
            </div>
        </div>
    </div>
</body>
</html>
