<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de Emergencia - {{ config('branding.name') }}</title>
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
            background-color: #E53E3E;
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
        .alert-box {
            background-color: #FEE2E2;
            border: 2px solid #E53E3E;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
            text-align: center;
        }
        .alert-box h2 {
            color: #E53E3E;
            margin: 0 0 12px 0;
            font-size: 20px;
        }
        .severity-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .severity-low {
            background-color: #D69E2E;
            color: #FFFFFF;
        }
        .severity-medium {
            background-color: #DD6B20;
            color: #FFFFFF;
        }
        .severity-high {
            background-color: #E53E3E;
            color: #FFFFFF;
        }
        .severity-critical {
            background-color: #742A2A;
            color: #FFFFFF;
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
        .description {
            background-color: #FEE2E2;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .description h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            color: #E53E3E;
        }
        .actions {
            text-align: center;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-block;
            background-color: #E53E3E;
            color: #FFFFFF;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
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
            color: #5BBFAD;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ALERTA DE EMERGENCIA</h1>
        </div>

        <div class="content">
            <div class="alert-box">
                <h2>{{ config('branding.messages.emergency_alert') }}</h2>
                <span class="severity-badge severity-{{ $severity ?? 'medium' }}">
                    Severidade: {{ ucfirst($severity ?? 'Media') }}
                </span>
            </div>

            @if(isset($description))
            <div class="description">
                <h3>Descricao</h3>
                <p>{{ $description }}</p>
            </div>
            @endif

            <div class="info-box">
                @if(isset($emergencyId))
                <div class="info-row">
                    <span class="info-label">ID da Emergencia</span>
                    <span class="info-value">#{{ $emergencyId }}</span>
                </div>
                @endif
                @if(isset($serviceRequestId))
                <div class="info-row">
                    <span class="info-label">Servico</span>
                    <span class="info-value">#{{ $serviceRequestId }}</span>
                </div>
                @endif
                @if(isset($registeredAt))
                <div class="info-row">
                    <span class="info-label">Registrado em</span>
                    <span class="info-value">{{ $registeredAt }}</span>
                </div>
                @endif
            </div>

            @if(isset($contactUrl))
            <div class="actions">
                <a href="{{ $contactUrl }}" class="btn">Entrar em Contato</a>
            </div>
            @endif

            <p style="font-size: 14px; color: #616E7C; text-align: center;">
                Nossa equipe esta trabalhando para resolver esta situacao o mais rapido possivel.
            </p>
        </div>

        <div class="footer">
            <p>{{ config('branding.email.signature_name') }}</p>
            <p>
                <a href="mailto:{{ config('branding.email.reply_to') }}">{{ config('branding.email.reply_to') }}</a>
            </p>
            <p style="margin-top: 12px; font-size: 12px;">
                Emergencia? Ligue: <strong>{{ config('operacao.emergency.alert_phone', '0800 000 0000') }}</strong>
            </p>
        </div>
    </div>
</body>
</html>
