<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro recebido — Carinho com Você</title>
    <style>
        body { font-family: Nunito, Inter, Arial, sans-serif; background:#F4F7F9; color:#1a2b32; }
        .wrap { max-width: 480px; margin: 3rem auto; background:#fff; padding:2rem; border-radius:12px; text-align:center; }
        h1 { color:#5BBFAD; }
        a { color:#5BBFAD; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Cadastro recebido</h1>
        <p>Obrigado{{ session('caregiver_name') ? ', '.session('caregiver_name') : '' }}. Vamos analisar seu perfil e falar com você pelo WhatsApp.</p>
        @if (session('caregiver_id'))
            <p>Protocolo: #{{ session('caregiver_id') }}</p>
        @endif
        <p><a href="{{ route('cadastro') }}">Enviar outro cadastro</a></p>
    </div>
</body>
</html>
