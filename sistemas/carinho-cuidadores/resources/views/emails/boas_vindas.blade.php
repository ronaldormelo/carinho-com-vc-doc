<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo(a) - {{ $brandName }}</title>
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
            color: #5BBFAD;
            font-size: 24px;
            font-weight: bold;
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
        .highlight {
            background-color: #F4F7F9;
            border-left: 4px solid #5BBFAD;
            padding: 16px;
            margin: 24px 0;
        }
        .highlight h3 {
            margin: 0 0 8px;
            color: #1F2933;
            font-size: 16px;
        }
        .highlight ol {
            margin: 0;
            padding-left: 20px;
            color: #616E7C;
        }
        .highlight li {
            margin-bottom: 8px;
        }
        .btn {
            display: inline-block;
            background-color: #5BBFAD;
            color: #FFFFFF;
            padding: 12px 32px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin: 16px 0;
        }
        .footer {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #E4E7EB;
            margin-top: 24px;
            color: #9AA5B1;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">{{ $brandName }}</div>
            </div>

            <h1>Bem-vindo(a), {{ $caregiver->name }}!</h1>

            <p>Estamos muito felizes em te-lo(a) como parte da nossa equipe de cuidadores.</p>

            <p>A {{ $brandName }} conecta familias a cuidadores qualificados, oferecendo um servico humano, confiavel e transparente.</p>

            <div class="highlight">
                <h3>Proximos passos para ativar seu cadastro:</h3>
                <ol>
                    <li>Complete todas as informacoes do seu perfil</li>
                    <li>Envie os documentos obrigatorios (RG, CPF, comprovante de endereco)</li>
                    <li>Informe sua disponibilidade de horarios</li>
                    <li>Assine o termo de responsabilidade digital</li>
                </ol>
            </div>

            <p>Assim que seu cadastro estiver completo e aprovado, voce comecara a receber oportunidades de servico compativeis com seu perfil e regiao de atuacao.</p>

            <div class="footer">
                <p>Qualquer duvida, estamos a disposicao.</p>
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
