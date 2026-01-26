<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuidador Alocado - {{ config('branding.name') }}</title>
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
        .success-box {
            background-color: #38A169;
            color: #FFFFFF;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
            text-align: center;
        }
        .success-box h2 {
            margin: 0;
            font-size: 18px;
        }
        .caregiver-card {
            background-color: #F4F7F9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
            text-align: center;
        }
        .caregiver-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #5BBFAD;
            color: #FFFFFF;
            font-size: 32px;
            line-height: 80px;
            margin: 0 auto 12px;
        }
        .caregiver-name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .caregiver-info {
            color: #616E7C;
            font-size: 14px;
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

            <div class="success-box">
                <h2>{{ config('branding.messages.caregiver_assigned') }}</h2>
            </div>

            <div class="caregiver-card">
                <div class="caregiver-avatar">
                    {{ substr($caregiverName ?? 'C', 0, 1) }}
                </div>
                <div class="caregiver-name">{{ $caregiverName ?? 'Cuidador(a)' }}</div>
                @if(isset($caregiverRating))
                <div class="caregiver-info">Avaliacao: {{ $caregiverRating }} estrelas</div>
                @endif
            </div>

            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Tipo de servico</span>
                    <span class="info-value">{{ $serviceType ?? 'N/A' }}</span>
                </div>
                @if(isset($startDate))
                <div class="info-row">
                    <span class="info-label">Data de inicio</span>
                    <span class="info-value">{{ $startDate }}</span>
                </div>
                @endif
                @if(isset($endDate))
                <div class="info-row">
                    <span class="info-label">Data de termino</span>
                    <span class="info-value">{{ $endDate }}</span>
                </div>
                @endif
            </div>

            <p class="message">
                Voce recebera lembretes e notificacoes sobre o andamento do servico.
                Qualquer duvida, estamos a disposicao!
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
