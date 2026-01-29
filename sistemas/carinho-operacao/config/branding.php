<?php

return [
    'name' => env('BRAND_NAME', 'Carinho com Voce'),
    'domain' => env('BRAND_DOMAIN', 'carinho.com.vc'),
    'subdomain' => env('OPERACAO_SUBDOMAIN', 'operacao.carinho.com.vc'),
    'voice' => [
        'tone' => 'empatico, respeitoso e objetivo',
        'language' => 'simples e direta',
    ],
    'colors' => [
        'primary' => '#5BBFAD',
        'secondary' => '#F4F7F9',
        'accent' => '#F5C6AA',
        'text' => '#1a2b32',
        'success' => '#38A169',
        'warning' => '#D69E2E',
        'danger' => '#E53E3E',
    ],
    'typography' => [
        'font_family' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
    ],
    'assets' => [
        'logo' => [
            'primary' => '/images/logo-transparente.webp',
            'white' => '/images/logo-white.webp',
            'icon' => '/images/logo-icon.webp',
            'favicon' => '/images/favicon.ico',
        ],
    ],
    'email' => [
        'signature_name' => env('BRAND_SIGNATURE_NAME', 'Equipe Carinho'),
        'reply_to' => env('BRAND_REPLY_TO', 'contato@carinho.com.vc'),
        'operacao_from' => env('OPERACAO_EMAIL_FROM', 'operacao@carinho.com.vc'),
    ],
    'messages' => [
        'service_started' => 'O atendimento foi iniciado. O(a) cuidador(a) ja chegou e esta pronto(a) para cuidar de voce.',
        'service_ended' => 'O atendimento foi finalizado com sucesso. Esperamos que tudo tenha corrido bem!',
        'caregiver_assigned' => 'Boa noticia! Encontramos o(a) cuidador(a) ideal para voce.',
        'caregiver_replaced' => 'Houve uma alteracao no seu atendimento. Um novo cuidador foi designado.',
        'schedule_reminder' => 'Lembrete: Seu atendimento esta agendado para amanha.',
        'emergency_alert' => 'Alerta importante sobre seu atendimento. Por favor, entre em contato.',
    ],
];
