<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato Assinado com Sucesso</title>
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
            background-color: #38A169;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            color: #FFFFFF;
            font-size: 24px;
            font-weight: 600;
        }
        .success-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .message {
            margin-bottom: 25px;
            color: #616E7C;
        }
        .document-info {
            background-color: #F4F7F9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .document-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .document-info strong {
            color: #1F2933;
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
        .btn-container {
            text-align: center;
            margin: 30px 0;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="success-icon">✓</div>
                <h1>Contrato Assinado!</h1>
            </div>
            <div class="content">
                <p class="message">
                    Parabens! Seu documento foi assinado com sucesso.
                    Uma copia assinada esta disponivel para download.
                </p>

                <div class="document-info">
                    <p><strong>Tipo de documento:</strong> {{ $documentType }}</p>
                    <p><strong>Data da assinatura:</strong> {{ date('d/m/Y H:i') }}</p>
                </div>

                <div class="btn-container">
                    <a href="{{ $downloadUrl }}" class="btn">Baixar Documento</a>
                </div>

                <p class="message" style="font-size: 14px;">
                    O link para download e valido por 24 horas. Recomendamos que baixe e guarde
                    uma copia do documento em seus arquivos.
                </p>
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
