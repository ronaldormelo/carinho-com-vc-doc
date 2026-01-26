<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembrete de Agendamento - {{ config('branding.name') }}</title>
    <style>
        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            background-color: #F4F7F9;
            margin: 0;
            padding: 20px;
            color: #1F2933;
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
        .reminder-box {
            background-color: #D69E2E;
            color: #FFFFFF;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
            text-align: center;
        }
        .reminder-box h2 {
            margin: 0 0 8px 0;
            font-size: 18px;
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
            color: #1F2933;
        }
        .actions {
            text-align: center;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            margin: 0 8px;
        }
        .btn-primary {
            background-color: #5BBFAD;
            color: #FFFFFF;
        }
        .btn-secondary {
            background-color: #F4F7F9;
            color: #1F2933;
            border: 1px solid #E4E7EB;
        }
        .footer {
            background-color: #F4F7F9;
            padding: 20px 24px;
            text-align: center;
            font-size: 14px;
            color: #616E7C;
        }
        .footer a {
            color: #5BBFAD;
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

            <div class="reminder-box">
                <h2>Lembrete de Agendamento</h2>
                <p>{{ config('branding.messages.schedule_reminder') }}</p>
            </div>

            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Data</span>
                    <span class="info-value">{{ $date ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Horario</span>
                    <span class="info-value">{{ $startTime ?? 'N/A' }} - {{ $endTime ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cuidador(a)</span>
                    <span class="info-value">{{ $caregiverName ?? 'N/A' }}</span>
                </div>
            </div>

            <p class="message">
                Precisa reagendar ou cancelar? Entre em contato conosco o mais rapido possivel.
            </p>

            @if(isset($confirmUrl) || isset($rescheduleUrl))
            <div class="actions">
                @if(isset($confirmUrl))
                <a href="{{ $confirmUrl }}" class="btn btn-primary">Confirmar</a>
                @endif
                @if(isset($rescheduleUrl))
                <a href="{{ $rescheduleUrl }}" class="btn btn-secondary">Reagendar</a>
                @endif
            </div>
            @endif
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
