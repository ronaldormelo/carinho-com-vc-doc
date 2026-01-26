<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificação - {{ $brandName }}</title>
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
            font-size: 22px;
            margin: 0 0 16px;
        }
        .message-content {
            background-color: #F4F7F9;
            border-radius: 6px;
            padding: 20px;
            margin: 24px 0;
            white-space: pre-line;
            color: #1F2933;
        }
        .footer {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #E4E7EB;
            margin-top: 24px;
            color: #9AA5B1;
            font-size: 14px;
        }
        .badge-success {
            display: inline-block;
            background-color: #C6F6D5;
            color: #22543D;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-warning {
            display: inline-block;
            background-color: #FEFCBF;
            color: #744210;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-danger {
            display: inline-block;
            background-color: #FED7D7;
            color: #742A2A;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-info {
            display: inline-block;
            background-color: #BEE3F8;
            color: #2A4365;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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

            <h1>
                @switch($type)
                    @case('activated')
                        <span class="badge-success">Cadastro Ativado</span>
                        @break
                    @case('deactivated')
                        <span class="badge-warning">Cadastro Desativado</span>
                        @break
                    @case('blocked')
                        <span class="badge-danger">Cadastro Bloqueado</span>
                        @break
                    @case('document_approved')
                        <span class="badge-success">Documento Aprovado</span>
                        @break
                    @case('document_rejected')
                        <span class="badge-danger">Documento Recusado</span>
                        @break
                    @default
                        <span class="badge-info">Notificação</span>
                @endswitch
            </h1>

            <p>Olá, {{ $caregiver->name }}!</p>

            <div class="message-content">{{ $message }}</div>

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
