<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio do Atendimento - {{ config('branding.name') }}</title>
    <style>
        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            background-color: #F4F7F9;
            margin: 0;
            padding: 20px;
            color: #1a2b32;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #FFFFFF;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #5BBFAD;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            color: #FFFFFF;
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 32px 24px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 16px;
        }
        .message {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .info-box {
            background-color: #F4F7F9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #E4E7EB;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #616E7C;
        }
        .info-value {
            color: #1a2b32;
        }
        .status-badge {
            display: inline-block;
            background-color: #38A169;
            color: #FFFFFF;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 500;
        }
        .footer {
            background-color: #F4F7F9;
            padding: 20px 24px;
            text-align: center;
            font-size: 14px;
            color: #616E7C;
        }
        .footer a {
            color: #325B5D;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset(config('branding.assets.logo.white')) }}" alt="{{ config('branding.name') }}" style="height: 50px; margin-bottom: 16px;" />
            <h1>{{ config('branding.name') }}</h1>
        </div>

        <div class="content">
            <p class="greeting">Ola, {{ $clientName ?? 'Cliente' }}!</p>

            <p class="message">
                {{ config('branding.messages.service_started') }}
            </p>

            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="status-badge">Em andamento</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cuidador(a)</span>
                    <span class="info-value">{{ $caregiverName ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Data</span>
                    <span class="info-value">{{ $date ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Horario de inicio</span>
                    <span class="info-value">{{ $startTime ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Previsao de termino</span>
                    <span class="info-value">{{ $endTime ?? 'N/A' }}</span>
                </div>
            </div>

            <p class="message">
                Voce recebera uma notificacao quando o atendimento for finalizado.
                Em caso de duvidas ou necessidade, entre em contato conosco.
            </p>
        </div>

        <div class="footer">
            <p>{{ config('branding.email.signature_name') }}</p>
            <p>
                <a href="mailto:{{ config('branding.email.reply_to') }}">{{ config('branding.email.reply_to') }}</a>
            </p>
        </div>
    </div>
</body>
</html>
