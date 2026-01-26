<?php

return [
    'name' => env('BRAND_NAME', 'Carinho com Voce'),
    'domain' => env('BRAND_DOMAIN', 'carinho.com.vc'),
    'voice' => [
        'tone' => 'empatico, respeitoso e objetivo',
        'language' => 'simples e direta',
    ],
    'colors' => [
        'primary' => '#5BBFAD',
        'secondary' => '#F4F7F9',
        'accent' => '#F5C6AA',
        'text' => '#1F2933',
    ],
    'typography' => [
        'font_family' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
    ],
    'assets' => [
        'logo' => [
            'primary' => '/images/logo-primary.png',
            'white' => '/images/logo-white.png',
            'icon' => '/images/logo-icon.png',
            'favicon' => '/images/favicon.ico',
        ],
    ],
    'email' => [
        'signature_name' => env('BRAND_SIGNATURE_NAME', 'Equipe Carinho'),
        'reply_to' => env('BRAND_REPLY_TO', 'contato@carinho.com.vc'),
    ],
];
