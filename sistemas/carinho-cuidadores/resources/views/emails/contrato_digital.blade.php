<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seu Contrato - {{ $brandName }}</title>
    <style>
        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            line-height: 1.6;
            color: #1F2933;
            background-color: #F4F7F9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background-color: #FFFFFF;
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 24px;
            border-bottom: 1px solid #E4E7EB;
            margin-bottom: 24px;
        }
        .logo {
            text-align: center;
        }
        .logo img {
            height: 50px;
            width: auto;
        }
        h1 {
            color: #1F2933;
            font-size: 24px;
            margin: 0 0 16px;
        }
        p {
            margin: 0 0 16px;
            color: #616E7C;
        }
        .btn {
            display: inline-block;
            background-color: #5BBFAD;
            color: #FFFFFF;
            padding: 14px 40px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 24px 0;
        }
        .btn:hover {
            background-color: #4AA89A;
        }
        .info-box {
            background-color: #E6F7F4;
            border-radius: 6px;
            padding: 16px;
            margin: 24px 0;
        }
        .info-box p {
            margin: 0;
            color: #1F2933;
            font-size: 14px;
        }
        .warning-box {
            background-color: #FEFCBF;
            border-radius: 6px;
            padding: 16px;
            margin: 24px 0;
            border-left: 4px solid #D69E2E;
        }
        .warning-box p {
            margin: 0;
            color: #744210;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #E4E7EB;
            margin-top: 24px;
            color: #9AA5B1;
            font-size: 14px;
        }
        .center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">
                    <img src="{{ asset(config('branding.assets.logo.primary')) }}" alt="{{ $brandName }}" />
                </div>
            </div>

            <h1>Olá, {{ $caregiver->name }}!</h1>

            <p>Seu <strong>Termo de Responsabilidade</strong> está pronto para assinatura.</p>

            <p>Este documento formaliza sua parceria com a {{ $brandName }} e estabelece os termos de prestação de serviço como cuidador(a).</p>

            <div class="center">
                <a href="{{ $signUrl }}" class="btn">Revisar e Assinar</a>
            </div>

            <div class="info-box">
                <p><strong>O que acontece após a assinatura?</strong></p>
                <p>Seu cadastro será ativado e você começará a receber oportunidades de serviço compatíveis com seu perfil, disponibilidade e região de atuação.</p>
            </div>

            <div class="warning-box">
                <p><strong>Importante:</strong> Leia atentamente o documento antes de assinar. Em caso de dúvidas, entre em contato conosco.</p>
            </div>

            <div class="footer">
                <p>Qualquer dúvida, estamos à disposição.</p>
                <p>Atenciosamente,<br><strong>Equipe {{ $brandName }}</strong></p>
                <p style="margin-top: 16px; font-size: 12px;">
                    Este e-mail foi enviado para {{ $caregiver->email }}.<br>
                    {{ $brandName }} - carinho.com.vc
                </p>
            </div>
        </div>
    </div>
</body>
</html>
